<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Session;
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

        $notifications = collect(Firebase::database()->getReference('notifications/' . $userId)->getValue() ?? [])
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
                    'action_required' => ($notification['type'] ?? null) === 'workspace_invitation' && !($notification['read'] ?? false),
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
}
