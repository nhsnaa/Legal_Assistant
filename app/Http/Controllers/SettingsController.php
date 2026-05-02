<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SettingsController extends Controller
{
    public function index()
    {
        $userId = session('firebase_user_id');
        $profileSettings = $userId
            ? (Firebase::database()->getReference('settings/profile/' . $userId)->getValue() ?? [])
            : [];
        $documentRules = Firebase::database()->getReference('settings/document_rules')->getValue() ?? [
            'max_upload_size' => 25,
            'allowed_file_types' => ['PDF', 'DOCX'],
            'file_naming_rule' => '',
        ];

        $user = (object) [
            'first_name' => $profileSettings['display_name'] ?? session('first_name'),
            'name' => session('user_name', 'User'),
            'email' => session('email', 'No Email'),
            'user_role' => session('user_role', 'staff'),
            'firm_name' => $profileSettings['firm_name'] ?? '',
            'office_address' => $profileSettings['office_address'] ?? '',
        ];

        return view('settings', compact('user', 'documentRules'));
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
            'file_naming_rule' => ['nullable', 'string', 'max:255'],
        ]);

        Firebase::database()->getReference('settings/document_rules')->set([
            'max_upload_size' => (int) $validated['max_upload_size'],
            'allowed_file_types' => array_values($validated['allowed_file_types']),
            'file_naming_rule' => $validated['file_naming_rule'] ?? '',
            'updated_at' => now()->toIso8601String(),
        ]);

        return redirect()->route('settings')->with('success', 'Document rules updated successfully.');
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
}
