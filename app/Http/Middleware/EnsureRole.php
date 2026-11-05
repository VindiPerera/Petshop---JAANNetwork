<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->role === $role, 403, 'You do not have access to this area.');
        if ($role === 'branch') {
            abort_unless($user->branch_id, 403, 'No branch assigned to this login.');
        }
        return $next($request);
    }
}
