<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;

class RegisterController extends Controller
{
    public function show()
    {
        if (auth()->check()) { 
            return redirect()->route('dashboard'); 
        }
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
                'name' => $fullName,
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'user_role' => $validated['userRole'],
                'created_at' => now()->toDateTimeString(),
            ];

            // Pushing to the 'users' reference in Realtime Database
            $database->getReference('users')->push($userData);

            return redirect()->route('login')->with('success', 'Registration successful!');

        } catch (\Throwable $e) {
            // Log the actual error for debugging in Vercel Logs
            error_log('Professional Site Register Error: ' . $e->getMessage());
            
            // Return back with the actual error visible on the screen
            return back()->withInput()->withErrors(['email' => 'System Error: ' . $e->getMessage()]);
        }
    }
}