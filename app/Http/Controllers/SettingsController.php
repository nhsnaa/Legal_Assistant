<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SettingsController extends Controller
{
    public function index()
    {
        $database = Firebase::database();
        $userId = session('firebase_user_id');
        $workspaceId = $this->currentWorkspaceId();
        $profileSettings = $userId
            ? ($database->getReference('settings/profile/' . $userId)->getValue() ?? [])
            : [];
        $documentRules = $database->getReference($this->currentWorkspaceSettingsPath('document_rules'))->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
        ];
        $workspaceUsers = collect($database->getReference('users')->getValue() ?? [])
            ->filter(function ($member, $id) use ($workspaceId, $userId) {
                if ($id === $userId) {
                    return true;
                }

                return ($member['workspace_id'] ?? null) === $workspaceId
                    || (($member['workspace_status'] ?? null) === 'pending' && ($member['invited_workspace_id'] ?? null) === $workspaceId);
            })
            ->map(function ($member, $id) use ($userId) {
                return (object) [
                    'id' => $id,
                    'name' => $member['name'] ?? trim(($member['first_name'] ?? '') . ' ' . ($member['last_name'] ?? '')),
                    'email' => $member['email'] ?? 'No Email',
                    'user_role' => $member['user_role'] ?? 'staff',
                    'workspace_status' => $member['workspace_status'] ?? 'active',
                    'is_owner' => $id === $userId,
                ];
            })
            ->values();

        $user = (object) [
            'first_name' => $profileSettings['display_name'] ?? session('first_name'),
            'name' => session('user_name', 'User'),
            'email' => session('email', 'No Email'),
            'user_role' => session('user_role', 'staff'),
            'firm_name' => $profileSettings['firm_name'] ?? '',
            'office_address' => $profileSettings['office_address'] ?? '',
        ];

        return view('settings', compact('user', 'documentRules', 'workspaceUsers'));
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'display_name' => ['required', 'string', 'max:255'],
            'firm_name' => ['nullable', 'string', 'max:255'],
            'office_address' => ['nullable', 'string', 'max:1000'],
        ]);

        $userId = session('firebase_user_id');

        if (!$userId) {
            return back()->withErrors(['error' => 'Please sign in again before updating your profile settings.']);
        }

        $database = Firebase::database();

        $database->getReference('settings/profile/' . $userId)->set([
            'display_name' => $validated['display_name'],
            'firm_name' => $validated['firm_name'] ?? '',
            'office_address' => $validated['office_address'] ?? '',
            'updated_at' => now()->toIso8601String(),
        ]);

        $firstName = trim(explode(' ', $validated['display_name'])[0] ?? $validated['display_name']);

        $database->getReference('users/' . $userId)->update([
            'first_name' => $firstName,
            'name' => $validated['display_name'],
        ]);

        session([
            'first_name' => $firstName,
            'user_name' => $validated['display_name'],
        ]);

        return redirect()->route('settings')->with('success', 'Profile and firm settings updated successfully.');
    }

    public function updateDocumentRules(Request $request)
    {
        $validated = $request->validate([
            'max_upload_size' => ['required', 'integer', 'in:25,50,100'],
            'allowed_file_types' => ['required', 'array', 'min:1'],
            'allowed_file_types.*' => ['string', 'in:PDF,DOCX,XLSX,JPG,PNG,TXT'],
        ]);

        Firebase::database()->getReference($this->currentWorkspaceSettingsPath('document_rules'))->set([
            'max_upload_size' => (int) $validated['max_upload_size'],
            'allowed_file_types' => array_values($validated['allowed_file_types']),
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('settings')->with('success', 'Document rules updated successfully.');
    }

    public function storeStaff(Request $request)
    {
        $validated = $request->validate([
            'form_context' => ['nullable', 'string'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:staff,lawyer,clerk,assistant'],
            'password' => ['required', 'string', 'min:4'],
        ]);

        $database = Firebase::database();
        $existingUsers = $database->getReference('users')->getValue() ?? [];
        $emailExists = collect($existingUsers)->contains(function ($member) use ($validated) {
            return strcasecmp($member['email'] ?? '', $validated['email']) === 0;
        });

        if ($emailExists) {
            return redirect()
                ->route('settings')
                ->withInput()
                ->withErrors(['email' => 'This email is already registered in the workspace.']);
        }

        $nameParts = preg_split('/\s+/', trim($validated['name'])) ?: [];
        $firstName = $nameParts[0] ?? $validated['name'];
        $lastName = trim(implode(' ', array_slice($nameParts, 1)));

        $newUserRef = $database->getReference('users')->push([
            'first_name' => $firstName,
            'last_name' => $lastName,
            'name' => trim($validated['name']),
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_role' => $validated['role'],
            'workspace_status' => 'active',
            'workspace_joined_at' => now()->toIso8601String(),
            'workspace_id' => $this->currentWorkspaceId(),
            'workspace_owner_id' => $this->currentWorkspaceOwnerId(),
            'created_at' => now()->toDateTimeString(),
        ]);

        $database->getReference('notifications/' . $newUserRef->getKey())->push([
            'title' => 'Welcome to ILA Workspace',
            'message' => 'Your account has been created as ' . ucfirst($validated['role']) . '. You can now sign in and start using the system.',
            'type' => 'account',
            'read' => false,
            'created_at' => now()->toIso8601String(),
            'link' => route('dashboard'),
        ]);

        return redirect()->route('settings')->with('success', 'Staff account created successfully.');
    }

    public function inviteStaff(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:staff,lawyer,clerk,assistant'],
        ]);

        $currentUserId = session('firebase_user_id');

        if (!$currentUserId) {
            return response()->json(['message' => 'Please sign in again before sending invitations.'], 401);
        }

        // Check if email is registered in Firebase Auth
        try {
            $firebaseUser = Firebase::auth()->getUserByEmail($validated['email']);
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            return response()->json(['message' => 'Email not registered. Use "Create Staff Account" instead.'], 404);
        }

        $database = Firebase::database();
        $users = collect($database->getReference('users')->getValue() ?? []);
        $match = $users->first(function ($member) use ($validated) {
            return strcasecmp($member['email'] ?? '', $validated['email']) === 0;
        });
        $matchedUserId = $users->search($match);

        if ($matchedUserId === $currentUserId) {
            return response()->json(['message' => 'You are already part of this workspace.'], 422);
        }

        $workspaceId = $this->currentWorkspaceId();

        if ($match && ($match['workspace_id'] ?? null) === $workspaceId && ($match['workspace_status'] ?? null) === 'active') {
            return response()->json(['message' => 'User is already in the workspace.'], 422);
        }

        // If user exists in users table, update to pending
        if ($match && $matchedUserId) {
            $database->getReference('users/' . $matchedUserId)->update([
                'workspace_status' => 'pending',
                'pending_role' => $validated['role'],
                'invited_workspace_id' => $workspaceId,
                'invited_workspace_owner_id' => $this->currentWorkspaceOwnerId(),
                'workspace_invited_at' => now()->toIso8601String(),
            ]);
        } else {
            // Create pending user entry
            $newUserRef = $database->getReference('users')->push([
                'email' => $validated['email'],
                'firebase_uid' => $firebaseUser->uid,
                'user_role' => $validated['role'],
                'workspace_status' => 'pending',
                'invited_workspace_id' => $workspaceId,
                'invited_workspace_owner_id' => $this->currentWorkspaceOwnerId(),
                'workspace_invited_at' => now()->toIso8601String(),
                'created_at' => now()->toDateTimeString(),
            ]);
            $matchedUserId = $newUserRef->getKey();
        }

        $database->getReference('notifications/' . $matchedUserId)->push([
            'title' => 'Workspace Invitation',
            'message' => 'You were invited to join the ILA workspace as ' . ucfirst($validated['role']) . '. Accept this invitation to activate your workspace access.',
            'type' => 'workspace_invitation',
            'read' => false,
            'created_at' => now()->toIso8601String(),
            'invited_role' => $validated['role'],
            'workspace_id' => $workspaceId,
            'workspace_owner_id' => $this->currentWorkspaceOwnerId(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Invitation sent.',
        ]);
    }

    public function checkEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $user = Firebase::auth()->getUserByEmail($validated['email']);
            return response()->json(['registered' => true, 'uid' => $user->uid]);
        } catch (\Kreait\Firebase\Exception\Auth\UserNotFound $e) {
            return response()->json(['registered' => false]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unable to check email'], 500);
        }
    }

    public function destroyStaff($id)
    {
        $currentUserId = session('firebase_user_id');

        if (!$currentUserId) {
            return redirect()->route('settings')->with('error', 'Please sign in again before managing staff accounts.');
        }

        if ($id === $currentUserId) {
            return redirect()->route('settings')->with('error', 'The workspace owner account cannot be deleted from this screen.');
        }

        $database = Firebase::database();
        $userRef = $database->getReference('users/' . $id);

        if (!$userRef->getValue()) {
            return redirect()->route('settings')->with('error', 'Staff account not found.');
        }

        $userRef->remove();
        $database->getReference('settings/profile/' . $id)->remove();
        $database->getReference('notifications/' . $id)->remove();

        return redirect()->route('settings')->with('success', 'Staff account deleted successfully.');
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        $userId = session('firebase_user_id');

        if (!$userId) {
            return back()->withErrors(['error' => 'Please sign in again before updating your password.']);
        }

        Firebase::database()->getReference('users/' . $userId)->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }

    public function destroyAccount()
    {
        $userId = session('firebase_user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        if ($userId === $this->currentWorkspaceOwnerId()) {
            return back()->with('error', 'The workspace owner account cannot be deleted from this menu.');
        }

        $database = Firebase::database();
        $database->getReference('users/' . $userId)->remove();
        $database->getReference('settings/profile/' . $userId)->remove();
        $database->getReference('notifications/' . $userId)->remove();

        session()->flush();

        return redirect()->route('login')->with('success', 'Account deleted successfully.');
    }
}
