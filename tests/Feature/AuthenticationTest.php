<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_masuk_memakai_username(): void
    {
        $user = User::factory()->create([
            'username' => 'petugas',
            'password' => 'rahasia123',
        ]);

        $response = $this->post('/login', [
            'username' => 'petugas',
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_salah_ditolak(): void
    {
        User::factory()->create([
            'username' => 'petugas',
            'password' => 'rahasia123',
        ]);

        $response = $this->post('/login', [
            'username' => 'petugas',
            'password' => 'salah',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_tamu_diarahkan_ke_halaman_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_pengguna_bisa_keluar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_pengguna_nonaktif_tidak_bisa_masuk(): void
    {
        User::factory()->create([
            'username' => 'nonaktif',
            'password' => 'rahasia123',
            'is_active' => false,
        ]);

        $response = $this->post('/login', [
            'username' => 'nonaktif',
            'password' => 'rahasia123',
        ]);

        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_pengguna_yang_dinonaktifkan_saat_sesi_aktif_langsung_dikeluarkan(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/dashboard')->assertOk();

        // Admin menonaktifkan akun ketika user ini masih login.
        $user->update(['is_active' => false]);

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['username' => 'Akun Anda dinonaktifkan. Hubungi admin.']);
        $this->assertGuest();
    }

    public function test_pengguna_nonaktif_ditolak_pada_permintaan_json(): void
    {
        $user = User::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->postJson('/chatbot/message', ['message' => 'Halo'])
            ->assertUnauthorized()
            ->assertJson(['error' => 'account_inactive']);
        $this->assertGuest();
    }

    public function test_halaman_login_menautkan_lupa_password_dan_alternatif_hubungi_admin(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Lupa password?');
        $response->assertSee(route('password.request'));
        $response->assertSee('Hubungi admin');
    }
}
