<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Keluarkan paksa user yang dinonaktifkan Admin saat sesinya masih hidup.
     * LoginController hanya memeriksa is_active ketika login, sehingga tanpa
     * middleware ini akun nonaktif tetap bisa dipakai sampai sesi kedaluwarsa.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            $message = 'Akun Anda dinonaktifkan. Hubungi admin.';

            if ($request->expectsJson()) {
                return response()->json(['error' => 'account_inactive', 'reply' => $message], 401);
            }

            return redirect()->route('login')->withErrors(['username' => $message]);
        }

        return $next($request);
    }
}
