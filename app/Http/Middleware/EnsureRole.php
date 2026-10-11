<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Batasi rute ke peran tertentu, mis. middleware('role:ADMIN,PETUGAS').
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->role->value, $roles, true)) {
            abort(403, 'Peran Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}
