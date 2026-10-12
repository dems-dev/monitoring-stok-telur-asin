<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_melihat_daftar_pengguna(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['name' => 'Petugas Toko']);

        $this->actingAs($admin)
            ->get('/users')
            ->assertOk()
            ->assertSee('Petugas Toko');
    }

    public function test_admin_dapat_menambah_pengguna_baru(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'username' => 'petugasbaru',
            'name' => 'Petugas Baru Brebes',
            'email' => 'baru@telurasin.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => Role::PETUGAS->value,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'username' => 'petugasbaru',
            'email' => 'baru@telurasin.com',
            'role' => Role::PETUGAS->value,
            'is_active' => true,
        ]);
    }

    public function test_admin_dapat_mereset_password_pengguna_lain(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create([
            'password' => Hash::make('password_lama'),
        ]);

        $response = $this->actingAs($admin)->put("/users/{$targetUser->id}", [
            'username' => $targetUser->username,
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => $targetUser->role->value,
            'password' => 'password_baru_123',
            'password_confirmation' => 'password_baru_123',
            'is_active' => true,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertTrue(Hash::check('password_baru_123', $targetUser->fresh()->password));
    }

    public function test_admin_dapat_menonaktifkan_pengguna_lain(): void
    {
        $admin = User::factory()->admin()->create();
        $targetUser = User::factory()->create(['is_active' => true]);

        $response = $this->actingAs($admin)->put("/users/{$targetUser->id}", [
            'username' => $targetUser->username,
            'name' => $targetUser->name,
            'email' => $targetUser->email,
            'role' => $targetUser->role->value,
            'is_active' => false,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertFalse($targetUser->fresh()->is_active);
    }

    public function test_admin_tidak_dapat_menonaktifkan_admin_terakhir(): void
    {
        $singleAdmin = User::factory()->admin()->create(['is_active' => true]);

        $response = $this->actingAs($singleAdmin)->put("/users/{$singleAdmin->id}", [
            'username' => $singleAdmin->username,
            'name' => $singleAdmin->name,
            'email' => $singleAdmin->email,
            'role' => Role::ADMIN->value,
            'is_active' => false,
        ]);

        // Karena akun sendiri, proteksi controller memaksakan is_active tetap true
        $this->assertTrue($singleAdmin->fresh()->is_active);
    }
}
