<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckStaffRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
{
    $role = session('role');
    if (!in_array($role, ['staff', 'admin'])) {
        return redirect()->route('login');
    }
    return $next($request);
}
}
