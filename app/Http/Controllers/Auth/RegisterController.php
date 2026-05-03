<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    public function show()
    {
        return view('register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255', // Removed unique:users for Firebase compatibility
            'userRole' => 'required|string|in:lawyer,staff',
            'password' => 'required|min:4|confirmed',
        ]);

        try {
            // Using absolute namespace to prevent "Class not found" on Vercel
            $database = \Kreait\Laravel\Firebase\Facades\Firebase::database();
            
            $fullName = trim($validated['first_name'].' '.$validated['last_name']);

            $userData = [
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'user_role' => $validated['userRole'],
                'created_at' => now()->toDateTimeString(),
            ];

            // Pushing to the 'users' reference in Realtime Database
            $newUserRef = $database->getReference('users')->push($userData);
            $newUserId = $newUserRef->getKey();

            if ($validated['userRole'] === 'lawyer') {
                // Lawyers automatically own their workspace
                $database->getReference('users/' . $newUserId)->update([
                    'workspace_id' => $newUserId,
                    'workspace_owner_id' => $newUserId,
                    'workspace_status' => 'active',
                    'workspace_joined_at' => now()->toIso8601String(),
                ]);
            } else {
                // Staff need to be invited to join a workspace
                $database->getReference('users/' . $newUserId)->update([
                    'workspace_status' => 'pending_invitation',
                ]);
            }

            return redirect()->route('login')->with('success', 'Registration successful!');

        } catch (\Throwable $e) {
            // Log the actual error for debugging in Vercel Logs
            error_log('Professional Site Register Error: ' . $e->getMessage());
            
            // Return back with the actual error visible on the screen
            return back()->withInput()->withErrors(['email' => 'System Error: ' . $e->getMessage()]);
        }
    }
}
