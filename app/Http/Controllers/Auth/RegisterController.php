<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;
// We remove the "use App\Http\Controllers\Controller;" line to avoid the crash

class RegisterController extends \App\Http\Controllers\Controller
{
    public function show()
    {
        if (auth()->check()) { return redirect()->route('dashboard'); }
        // Make sure this view exists in resources/views/register.blade.php
        return view('register'); 
    }

    public function store(Request $request)
    {
        // ... your existing Firebase code is fine here ...
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'userRole' => 'required|string|in:lawyer,staff',
            'password' => 'required|min:4|confirmed',
        ]);

        $database = Firebase::database();
        $fullName = trim($validated['first_name'].' '.$validated['last_name']);

        $userData = [
            'name' => $fullName,
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'user_role' => $validated['userRole'],
            'created_at' => now()->toDateTimeString(),
        ];

        $database->getReference('users')->push($userData);

        return redirect()->route('login')->with('success', 'Registration successful! Please login.');
    }
}