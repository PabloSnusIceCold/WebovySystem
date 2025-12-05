<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // If the user is not logged in or is not an admin, redirect back to home with an error message
        if (! $user instanceof User || $user->role !== 'admin') {
            return redirect()->route('home')->with('error', 'Nemáš oprávnenie.');
        }

        return $next($request);
    }
}
