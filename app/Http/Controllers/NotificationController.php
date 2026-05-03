<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $userId = session('firebase_user_id');

        if (!$userId) {
            return response()->json([
                'notifications' => [],
                'unread_count' => 0,
            ]);
        }

        $preferences = array_merge(
            $this->defaultNotificationSettings(),
            Firebase::database()->getReference('settings/profile/' . $userId . '/notification_preferences')->getValue() ?? []
        );

        $notifications = collect(Firebase::database()->getReference('notifications/' . $userId)->getValue() ?? [])
            ->filter(function ($notification) use ($preferences) {
                return match ($notification['type'] ?? 'general') {
                    'schedule' => (bool) $preferences['deadline_reminders'],
                    'document' => (bool) $preferences['document_alerts'],
                    'time_summary' => (bool) $preferences['time_entry_summary'],
                    default => true,
                };
            })
            ->map(function ($notification, $id) {
                return [
                    'id' => $id,
                    'title' => $notification['title'] ?? 'Notification',
                    'message' => $notification['message'] ?? '',
                    'type' => $notification['type'] ?? 'general',
                    'read' => (bool) ($notification['read'] ?? false),
                    'created_at' => $notification['created_at'] ?? now()->toIso8601String(),
                    'link' => $notification['link'] ?? null,
                    'invited_role' => $notification['invited_role'] ?? null,
                    'action_required' => in_array(($notification['type'] ?? null), ['workspace_invitation', 'password_reset_request'], true)
                        && empty($notification['resolved_at']),
                    'action_label' => match ($notification['type'] ?? null) {
                        'workspace_invitation' => 'Accept Invitation',
                        'password_reset_request' => 'Set Temporary Password',
                        default => null,
                    },
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $notifications->where('read', false)->count(),
        ]);
    }

    public function markAsRead(string $id): JsonResponse
    {
        $userId = session('firebase_user_id');

        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $notificationRef = Firebase::database()->getReference('notifications/' . $userId . '/' . $id);

        if (!$notificationRef->getValue()) {
            return response()->json(['message' => 'Notification not found.'], 404);
        }

        $notificationRef->update([
            'read' => true,
            'read_at' => now()->toIso8601String(),
        ]);

        return response()->json(['success' => true]);
    }

    public function acceptInvitation(string $id): JsonResponse
    {
        $userId = session('firebase_user_id');

        if (!$userId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $database = Firebase::database();
        $notificationRef = $database->getReference('notifications/' . $userId . '/' . $id);
        $notification = $notificationRef->getValue();

        if (!$notification) {
            return response()->json(['message' => 'Invitation not found.'], 404);
        }

        if (($notification['type'] ?? '') !== 'workspace_invitation') {
            return response()->json(['message' => 'This notification is not an invitation.'], 422);
        }

        $role = $notification['invited_role'] ?? 'staff';
        $workspaceId = $notification['workspace_id'] ?? $userId;
        $workspaceOwnerId = $notification['workspace_owner_id'] ?? $workspaceId;

        $database->getReference('users/' . $userId)->update([
            'user_role' => $role,
            'workspace_id' => $workspaceId,
            'workspace_owner_id' => $workspaceOwnerId,
            'workspace_status' => 'active',
            'workspace_joined_at' => now()->toIso8601String(),
            'pending_role' => null,
            'invited_workspace_id' => null,
            'invited_workspace_owner_id' => null,
        ]);

        $notificationRef->update([
            'read' => true,
            'accepted_at' => now()->toIso8601String(),
            'link' => route('dashboard'),
        ]);

        Session::put('user_role', $role);
        Session::put('workspace_id', $workspaceId);
        Session::put('workspace_owner_id', $workspaceOwnerId);
        Session::put('workspace_status', 'active');

        return response()->json([
            'success' => true,
            'message' => 'Invitation accepted. Your workspace access is now active.',
        ]);
    }

    public function resetPasswordRequest(Request $request, string $id): JsonResponse
    {
        $request->validate([
            'temp_password' => ['required', 'string', 'min:4'],
        ]);

        $ownerId = session('firebase_user_id');

        if (!$ownerId) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if ($ownerId !== $this->currentWorkspaceOwnerId()) {
            return response()->json(['message' => 'Only the workspace owner can reset requested passwords.'], 403);
        }

        $database = Firebase::database();
        $notificationRef = $database->getReference('notifications/' . $ownerId . '/' . $id);
        $notification = $notificationRef->getValue();

        if (!$notification) {
            return response()->json(['message' => 'Reset request not found.'], 404);
        }

        if (($notification['type'] ?? '') !== 'password_reset_request') {
            return response()->json(['message' => 'This notification is not a password reset request.'], 422);
        }

        $targetUserId = $notification['requester_user_id'] ?? null;

        if (!$targetUserId) {
            return response()->json(['message' => 'The requested account could not be identified.'], 422);
        }

        $targetUserRef = $database->getReference('users/' . $targetUserId);
        $targetUser = $targetUserRef->getValue();

        if (!$targetUser) {
            return response()->json(['message' => 'The requested account no longer exists.'], 404);
        }

        $belongsToWorkspace = ($targetUser['workspace_id'] ?? null) === $this->currentWorkspaceId()
            || (($targetUser['invited_workspace_id'] ?? null) === $this->currentWorkspaceId());

        if (!$belongsToWorkspace) {
            return response()->json(['message' => 'This account does not belong to your workspace.'], 403);
        }

        $passwordPolicyError = $this->validatePasswordAgainstPolicy(
            $request->temp_password,
            $this->currentWorkspaceSecuritySettings()['password_policy'] ?? 'strong'
        );

        if ($passwordPolicyError) {
            return response()->json(['message' => $passwordPolicyError], 422);
        }

        $targetUserRef->update([
            'password' => Hash::make($request->temp_password),
        ]);

        $notificationRef->update([
            'read' => true,
            'resolved_at' => now()->toIso8601String(),
            'resolved_by' => $ownerId,
        ]);

        $database->getReference('notifications/' . $targetUserId)->push([
            'title' => 'Password Reset Ready',
            'message' => 'Your workspace owner has reset your password. Please use the temporary password they provide and change it after signing in.',
            'type' => 'account',
            'read' => false,
            'created_at' => now()->toIso8601String(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Temporary password saved. Share it with the user so they can sign in again.',
        ]);
    }
}
