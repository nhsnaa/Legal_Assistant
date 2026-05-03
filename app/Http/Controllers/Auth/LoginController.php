<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    public function show()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // Force verify that the credentials string exists
            if (!env('FIREBASE_CREDENTIALS')) {
                return back()->withErrors(['email' => 'System Error: FIREBASE_CREDENTIALS missing in Vercel settings.']);
            }

            // Using the app helper now that the package is properly installed
            $database = app('firebase.database');

            try {
                $usersRef = $database->getReference('users')
                    ->orderByChild('email')
                    ->equalTo($credentials['email'])
                    ->getValue();
            } catch (\Throwable $queryException) {
                // Fallback for projects that haven't added .indexOn rules yet.
                if (!str_contains($queryException->getMessage(), '.indexOn')) {
                    throw $queryException;
                }

                $allUsers = $database->getReference('users')->getValue() ?? [];
                $usersRef = [];

                foreach ($allUsers as $userId => $userData) {
                    if (($userData['email'] ?? null) === $credentials['email']) {
                        $usersRef[$userId] = $userData;
                        break;
                    }
                }
            }

            if (!$usersRef) {
                return back()->withErrors(['email' => 'User not found in database. Please register first.']);
            }

            $userId = array_key_first($usersRef);
            $userData = $usersRef[$userId];

            // Verify password against the hashed version in Firebase
            if (Hash::check($credentials['password'], $userData['password'])) {
                $workspaceStatus = $userData['workspace_status'] ?? 'active';
                $workspaceId = $workspaceStatus === 'pending_invitation'
                    ? ($userData['invited_workspace_id'] ?? null)
                    : ($userData['workspace_id'] ?? $userId);
                $workspaceOwnerId = $workspaceStatus === 'pending_invitation'
                    ? ($userData['invited_workspace_owner_id'] ?? null)
                    : ($userData['workspace_owner_id'] ?? $workspaceId);

                if ($workspaceStatus !== 'pending_invitation') {
                    $database->getReference('users/' . $userId)->update([
                        'workspace_id' => $workspaceId,
                        'workspace_owner_id' => $workspaceOwnerId,
                        'workspace_status' => $workspaceStatus,
                    ]);
                }

                if ($workspaceOwnerId === $userId && $workspaceStatus !== 'pending_invitation') {
                    $this->backfillWorkspaceData($database, $workspaceId, $workspaceOwnerId);
                }

                // Manual session persistence for Vercel 'cookie' driver
                Session::put('firebase_user_id', $userId);
                Session::put('user_role', $userData['user_role']);
                Session::put('first_name', $userData['first_name'] ?? null);
                Session::put('user_name', $userData['name'] ?? 'User');
                Session::put('email', $userData['email'] ?? $credentials['email']);
                Session::put('workspace_id', $workspaceId);
                Session::put('workspace_owner_id', $workspaceOwnerId);
                Session::put('workspace_status', $workspaceStatus);
                Session::save(); 

                return redirect()->route('dashboard');
            }

        } catch (\Throwable $e) {
            error_log('Vercel Login Crash: ' . $e->getMessage());
            
            // This catches the "Value missing" error specifically
            if (str_contains($e->getMessage(), 'ServiceAccount')) {
                return back()->withErrors(['email' => 'Firebase Auth Error: Ensure the FIREBASE_CREDENTIALS in Vercel is the full JSON text.']);
            }
            
            return back()->withErrors(['email' => 'Firebase Error: ' . $e->getMessage()]);
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }

    public function requestPasswordReset(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            $database = app('firebase.database');
            $users = $database->getReference('users')->getValue() ?? [];
            $matchedUserId = null;
            $matchedUser = null;

            foreach ($users as $userId => $userData) {
                if (strcasecmp($userData['email'] ?? '', $validated['email']) === 0) {
                    $matchedUserId = $userId;
                    $matchedUser = $userData;
                    break;
                }
            }

            if (!$matchedUserId || !$matchedUser) {
                return back()->withErrors(['email' => 'No registered account was found for that email.']);
            }

            $workspaceOwnerId = ($matchedUser['workspace_status'] ?? 'active') === 'pending_invitation'
                ? ($matchedUser['invited_workspace_owner_id'] ?? null)
                : ($matchedUser['workspace_owner_id'] ?? $matchedUserId);

            if (!$workspaceOwnerId || $workspaceOwnerId === $matchedUserId) {
                return back()->withErrors([
                    'email' => 'This account cannot be reset through workspace requests yet. Please contact the system administrator directly.',
                ]);
            }

            $owner = $users[$workspaceOwnerId] ?? null;

            if (!$owner) {
                return back()->withErrors([
                    'email' => 'The workspace owner for this account could not be found. Please contact the system administrator.',
                ]);
            }

            $ownerNotificationsRef = $database->getReference('notifications/' . $workspaceOwnerId);
            $existingNotifications = $ownerNotificationsRef->getValue() ?? [];

            foreach ($existingNotifications as $notificationId => $notification) {
                if (
                    ($notification['type'] ?? null) === 'password_reset_request'
                    && ($notification['requester_user_id'] ?? null) === $matchedUserId
                    && empty($notification['resolved_at'])
                ) {
                    return back()->with('success', 'A password reset request has already been sent to your workspace owner.');
                }
            }

            $requesterName = $matchedUser['name']
                ?? trim(($matchedUser['first_name'] ?? '') . ' ' . ($matchedUser['last_name'] ?? ''))
                ?: 'Workspace User';

            $ownerNotificationsRef->push([
                'title' => 'Password Reset Request',
                'message' => $requesterName . ' requested a temporary password for their workspace account.',
                'type' => 'password_reset_request',
                'read' => false,
                'created_at' => now()->toIso8601String(),
                'requester_user_id' => $matchedUserId,
                'requester_email' => $matchedUser['email'] ?? $validated['email'],
                'requester_name' => $requesterName,
                'workspace_id' => ($matchedUser['workspace_status'] ?? 'active') === 'pending_invitation'
                    ? ($matchedUser['invited_workspace_id'] ?? null)
                    : ($matchedUser['workspace_id'] ?? null),
                'workspace_owner_id' => $workspaceOwnerId,
            ]);

            return back()->with('success', 'Your password reset request was sent to your workspace owner.');
        } catch (\Throwable $e) {
            error_log('Forgot Password Request Error: ' . $e->getMessage());

            return back()->withErrors([
                'email' => 'Unable to send the password reset request right now. Please try again later.',
            ]);
        }
    }

    private function backfillWorkspaceData($database, string $workspaceId, string $workspaceOwnerId): void
    {
        foreach (['clients', 'cases', 'documents', 'hearings', 'time_entries'] as $node) {
            $records = $database->getReference($node)->getValue() ?? [];

            foreach ($records as $recordId => $record) {
                if (!empty($record['workspace_id'])) {
                    continue;
                }

                $database->getReference($node . '/' . $recordId)->update([
                    'workspace_id' => $workspaceId,
                    'workspace_owner_id' => $workspaceOwnerId,
                ]);
            }
        }

        if (!($database->getReference('settings/workspaces/' . $workspaceId . '/document_rules')->getValue())) {
            $legacyRules = $database->getReference('settings/document_rules')->getValue();

            if ($legacyRules) {
                $database->getReference('settings/workspaces/' . $workspaceId . '/document_rules')->set($legacyRules);
            }
        }
    }
}
