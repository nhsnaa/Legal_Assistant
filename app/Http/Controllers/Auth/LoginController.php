<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Laravel\Firebase\Facades\Firebase;
// Import your Firebase library or service here
// use Kreait\Firebase\Contract\Auth as FirebaseAuth; 

class LoginController extends Controller
{
    public function show()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            // 1. Here is where you would call Firebase to sign in
            // $signInResult = $this->firebaseAuth->signInWithEmailAndPassword($email, $password);
            
            // 2. For now, to just get the login page to LOAD without crashing:
            // We will leave the logic blank or use a simple check
            
            return redirect()->intended('dashboard');

        } catch (\Exception $e) {
            return back()->withErrors([
                'email' => 'Firebase Authentication failed: ' . $e->getMessage(),
            ]);
        }
    }
}