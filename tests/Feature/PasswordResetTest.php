<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_password_bisa_dibuka_tamu(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Kirim Tautan Reset');
    }

    public function test_tautan_reset_dikirim_ke_email_terdaftar(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'siti@telur.com']);

        $this->post(route('password.email'), ['email' => 'siti@telur.com'])
            ->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_email_tidak_terdaftar_mendapat_pesan_yang_sama_tanpa_email_terkirim(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'siti@telur.com']);

        $registered = $this->post(route('password.email'), ['email' => 'siti@telur.com'])->getSession()->get('status');
        $unknown = $this->post(route('password.email'), ['email' => 'tidak.ada@telur.com'])->getSession()->get('status');

        // Respons identik supaya penyerang tidak bisa menebak email mana yang terdaftar.
        $this->assertSame($registered, $unknown);
        Notification::assertSentTimes(ResetPassword::class, 1);
    }

    public function test_email_reset_berbahasa_indonesia_dan_menautkan_halaman_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Reset Password SIM-STOK Telur Asin'
                && $mail->actionText === 'Buat Password Baru'
                && str_contains($mail->actionUrl, route('password.reset', $notification->token, false));
        });
    }

    public function test_password_bisa_diganti_dengan_token_yang_valid(): void
    {
        $user = User::factory()->create(['email' => 'siti@telur.com', 'password' => 'passwordlama']);
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Simpan Password Baru');

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'siti@telur.com',
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('passwordbaru123', $user->fresh()->password));

        // Token sekali pakai: tidak bisa dipakai ulang.
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'siti@telur.com',
            'password' => 'passwordlain456',
            'password_confirmation' => 'passwordlain456',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('passwordbaru123', $user->fresh()->password));
    }

    public function test_token_palsu_ditolak(): void
    {
        $user = User::factory()->create(['password' => 'passwordlama']);

        $this->post(route('password.update'), [
            'token' => 'token-palsu',
            'email' => $user->email,
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('passwordlama', $user->fresh()->password));
    }

    public function test_password_baru_minimal_8_karakter_dan_harus_dikonfirmasi(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'pendek',
            'password_confirmation' => 'berbeda',
        ])->assertSessionHasErrors('password');
    }
}
