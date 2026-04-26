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
            // Using the app helper is the most stable way on Vercel
            $database = app('firebase.database');
            
            $usersRef = $database->getReference('users')
                ->orderByChild('email')
                ->equalTo($credentials['email'])
                ->getValue();

            if (!$usersRef) {
                return back()->withErrors(['email' => 'User not found in database. Please register again.']);
            }

            $userId = array_key_first($usersRef);
            $userData = $usersRef[$userId];

            if (Hash::check($credentials['password'], $userData['password'])) {
                Session::put('firebase_user_id', $userId);
                Session::put('user_role', $userData['user_role']);
                Session::save(); 

                return redirect()->route('dashboard');
            }

        } catch (\Throwable $e) {
            error_log('Vercel Login Crash: ' . $e->getMessage());
            // This will tell you EXACTLY what is missing (e.g. "Service Account not found")
            return back()->withErrors(['email' => 'Firebase Error: ' . $e->getMessage()]);
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }
}