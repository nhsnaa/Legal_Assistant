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
            $database = \Kreait\Laravel\Firebase\Facades\Firebase::database();
            $usersRef = $database->getReference('users')
                ->orderByChild('email')
                ->equalTo($credentials['email'])
                ->getValue();

            if ($usersRef) {
                $userId = array_key_first($usersRef);
                $userData = $usersRef[$userId];

                if (Hash::check($credentials['password'], $userData['password'])) {
                    // Manual session storage for Vercel stability
                    Session::put('firebase_user_id', $userId);
                    Session::put('user_role', $userData['user_role']);
                    Session::save(); 

                    return redirect()->route('dashboard');
                }
            }
        } catch (\Throwable $e) {
            error_log('Login Error: ' . $e->getMessage());
            return back()->withErrors(['email' => 'System Error: ' . $e->getMessage()]);
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }
}