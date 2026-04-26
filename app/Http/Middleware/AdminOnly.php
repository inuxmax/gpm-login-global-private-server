<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AdminOnly
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        if ($user === null) {
            if ($request->expectsJson() || $request->is('admin/api/*')) {
                return response()->json(['success' => false, 'message' => 'unauthenticated'], 401);
            }
            return redirect('/admin/auth');
        }

        if ($user->system_role !== User::ROLE_ADMIN) {
            if ($request->expectsJson() || $request->is('admin/api/*')) {
                return response()->json(['success' => false, 'message' => 'forbidden'], 403);
            }
            return redirect('/admin/auth');
        }

        return $next($request);
    }
}
