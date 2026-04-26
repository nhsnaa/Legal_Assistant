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
        if (auth()->check()) { 
            return redirect()->route('dashboard'); 
        }
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
            
            $usersRef = $database->getReference('users')
                ->orderByChild('email')
                ->equalTo($credentials['email'])
                ->getValue();

            if (!$usersRef) {
                return back()->withErrors(['email' => 'User not found in database. Please register first.']);
            }

            $userId = array_key_first($usersRef);
            $userData = $usersRef[$userId];

            // Verify password against the hashed version in Firebase
            if (Hash::check($credentials['password'], $userData['password'])) {
                // Manual session persistence for Vercel 'cookie' driver
                Session::put('firebase_user_id', $userId);
                Session::put('user_role', $userData['user_role']);
                Session::put('user_name', $userData['name'] ?? 'User');
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
}