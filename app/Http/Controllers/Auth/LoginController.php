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
                $workspaceId = $userData['workspace_id'] ?? $userId;
                $workspaceOwnerId = $userData['workspace_owner_id'] ?? $workspaceId;
                $workspaceStatus = $userData['workspace_status'] ?? 'active';

                $database->getReference('users/' . $userId)->update([
                    'workspace_id' => $workspaceId,
                    'workspace_owner_id' => $workspaceOwnerId,
                    'workspace_status' => $workspaceStatus,
                ]);

                if ($workspaceOwnerId === $userId) {
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
