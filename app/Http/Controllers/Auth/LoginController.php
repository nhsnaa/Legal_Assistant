<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
// Using the absolute path to the Facade to avoid "Class not found"
use Kreait\Laravel\Firebase\Facades\Firebase;

class LoginController extends Controller
{
    public function show()
    {
        // Matches C:\xampp\htdocs\ILA-System\resources\views\login.blade.php
        return view('login');
    }

    public function store(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        try {
            // This will now find the class correctly
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
        } catch (\Exception $e) {
            // This will now show up in your Vercel "Runtime" logs 
            // because of our previous errorlog fix
            error_log('Firebase Login Error: ' . $e->getMessage());
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }
}