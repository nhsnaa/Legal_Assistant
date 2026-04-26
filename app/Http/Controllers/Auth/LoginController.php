<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;

class LoginController extends \App\Http\Controllers\Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            $database = Firebase::database();
            $usersRef = $database->getReference('users')
                ->orderByChild('email')
                ->equalTo($credentials['email'])
                ->getValue();

            if ($usersRef) {
                $userId = array_key_first($usersRef);
                $userData = $usersRef[$userId];

                if (Hash::check($credentials['password'], $userData['password'])) {
                    session(['firebase_user_id' => $userId, 'user_role' => $userData['user_role']]);
                    return redirect()->route('dashboard');
                }
            }
        } catch (\Exception $e) {
            // Log error if needed
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }
}