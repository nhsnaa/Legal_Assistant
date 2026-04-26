<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;

class RegisterController extends Controller
{
    public function show()
    {
        if (auth()->check()) { return redirect()->route('dashboard'); }
        return view('register');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'userRole' => 'required|string|in:lawyer,staff',
            'password' => 'required|min:4|confirmed',
        ]);

        $database = Firebase::database();
        $fullName = trim($validated['first_name'].' '.$validated['last_name']);

        // Create the user data array
        $userData = [
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']), // Securely hash
            'user_role' => $validated['userRole'],
            'created_at' => now()->toDateTimeString(),
        ];

        // Push to Firebase Realtime Database
        $database->getReference('users')->push($userData);

        return redirect()->route('login')->with('success', 'Registration successful! Please login.');
    }
}