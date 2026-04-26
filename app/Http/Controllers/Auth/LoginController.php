<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
// Ensure we use the absolute path for Vercel
use Kreait\Laravel\Firebase\Facades\Firebase;

class LoginController extends Controller
{
    public function show()
    {
        // Fixed: Points directly to resources/views/login.blade.php
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // Defensive check for Group 19's Firebase setup
            if (!class_exists('Kreait\Laravel\Firebase\Facades\Firebase')) {
                error_log('Error: Firebase Facade class is missing on Vercel.');
                return back()->withErrors(['email' => 'System configuration error.']);
            }

            $database = Firebase::database();
            $usersRef = $database->getReference('users')
                ->orderByChild('email')
                ->equalTo($credentials['email'])
                ->getValue();

            if ($usersRef) {
                $userId = array_key_first($usersRef);
                $userData = $usersRef[$userId];

                if (Hash::check($credentials['password'], $userData['password'])) {
                    session([
                        'firebase_user_id' => $userId, 
                        'user_role' => $userData['user_role']
                    ]);
                    return redirect()->route('dashboard');
                }
            }
        } catch (\Throwable $e) {
            // Catching all errors to prevent the "Read-only" 500 loop
            error_log('Group 19 Login Error: ' . $e->getMessage());
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }
}