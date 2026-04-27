<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class FirebaseAuth
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('firebase_user_id')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please sign in to continue.',
            ]);
        }

        return $next($request);
    }
}
