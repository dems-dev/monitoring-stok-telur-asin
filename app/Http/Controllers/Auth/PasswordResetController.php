<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Lupa password lewat tautan reset berbasis token yang dikirim ke email.
 * Token disimpan ter-hash di tabel password_reset_tokens dan kedaluwarsa
 * setelah 60 menit (config/auth.php).
 */
class PasswordResetController extends Controller
{
    /** Pesan sama untuk email terdaftar maupun tidak, agar daftar akun tidak bisa ditebak. */
    private const LINK_SENT_MESSAGE = 'Jika email tersebut terdaftar, tautan reset password telah dikirim. Periksa kotak masuk Anda.';

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']], [], ['email' => 'Email']);

        // Status INVALID_USER / RESET_THROTTLED sengaja tidak dibedakan di respons.
        Password::sendResetLink($request->only('email'));

        return back()->with('status', self::LINK_SENT_MESSAGE);
    }

    public function edit(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ], [], [
            'email' => 'Email',
            'password' => 'Password baru',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                // Cast 'hashed' di model User meng-hash password secara otomatis.
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => 'Tautan reset tidak valid atau sudah kedaluwarsa. Silakan minta tautan baru.',
            ]);
        }

        return redirect()
            ->route('login')
            ->with('status', 'Password berhasil diubah. Silakan masuk dengan password baru.');
    }
}
