<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Kreait\Laravel\Firebase\Facades\Firebase;

class SettingsController extends Controller
{
    public function index()
    {
        $user = (object) [
            'first_name' => session('first_name'),
            'name' => session('user_name', 'User'),
            'email' => session('email', 'No Email'),
            'user_role' => session('user_role', 'staff'),
        ];

        return view('settings', compact('user'));
    }

    public function updatePassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        $userId = session('firebase_user_id');

        if (!$userId) {
            return back()->withErrors(['error' => 'Please sign in again before updating your password.']);
        }

        Firebase::database()->getReference('users/' . $userId)->update([
            'password' => Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password updated successfully.');
    }
}
