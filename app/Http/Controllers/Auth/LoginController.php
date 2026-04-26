<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;

class LoginController extends Controller
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

        $database = Firebase::database();
        // Look for the user in your 'users' reference
        $usersRef = $database->getReference('users')->orderByChild('email')->equalTo($credentials['email'])->getValue();

        if ($usersRef) {
            // Firebase returns an array, get the first match
            $userId = array_key_first($usersRef);
            $userData = $usersRef[$userId];

            // Verify the hashed password
            if (Hash::check($credentials['password'], $userData['password'])) {
                // Manually log them into the session
                session(['firebase_user_id' => $userId, 'user_role' => $userData['user_role']]);
                
                return redirect()->route('dashboard');
            }
        }

        return back()->withErrors(['email' => 'Invalid email or password.']);
    }
}