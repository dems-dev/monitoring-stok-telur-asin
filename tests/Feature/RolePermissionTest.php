<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_tidak_bisa_membuka_form_mutasi(): void
    {
        $owner = User::factory()->owner()->create();

        $this->actingAs($owner)->get('/barang-masuk')->assertForbidden();
        $this->actingAs($owner)->get('/barang-keluar')->assertForbidden();
    }

    public function test_owner_tidak_bisa_mengirim_mutasi(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $this->actingAs($owner)->post('/barang-keluar', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 10,
            'status' => 'Terjual',
            'source_or_target' => 'Toko',
        ])->assertForbidden();

        $this->assertSame(100, $product->fresh()->stock);
    }

    public function test_petugas_tidak_bisa_mengelola_master_produk(): void
    {
        $petugas = User::factory()->create();
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        $this->actingAs($petugas)->get('/products/create')->assertForbidden();
        $this->actingAs($petugas)->post('/products', [
            'sku' => 'TLR-BARU-01',
            'name' => 'Telur Asin Baru',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => 3500,
            'min_stock' => 5,
        ])->assertForbidden();
    }

    public function test_petugas_tetap_bisa_melihat_katalog_dan_laporan(): void
    {
        $petugas = User::factory()->create();

        $this->actingAs($petugas)->get('/products')->assertOk();
        $this->actingAs($petugas)->get('/laporan')->assertOk();
        $this->actingAs($petugas)->get('/dashboard')->assertOk();
    }

    public function test_hanya_admin_yang_bisa_mengelola_pengguna(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($admin)->get('/users')->assertOk();
        $this->actingAs($petugas)->get('/users')->assertForbidden();
        $this->actingAs($owner)->get('/users')->assertForbidden();
    }

    public function test_admin_bisa_menambah_produk(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        $this->actingAs($admin)->post('/products', [
            'sku' => 'TLR-BARU-01',
            'name' => 'Telur Asin Baru',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => 3500,
            'purchase_price' => 2800,
            'min_stock' => 5,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'TLR-BARU-01', 'stock' => 0]);
    }

    public function test_admin_tidak_bisa_menghapus_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        $response->assertSessionHasErrors('user');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create(['is_active' => true]);

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'username' => $admin->username,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role->value,
            'is_active' => false,
        ]);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_admin_tidak_bisa_mengubah_peran_akun_sendiri(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put("/users/{$admin->id}", [
            'username' => $admin->username,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => 'PETUGAS',
        ]);

        $this->assertSame(Role::ADMIN, $admin->fresh()->role);
    }

    public function test_admin_bisa_mengubah_password_user_lain(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create([
            'username' => 'petugas_lama',
            'password' => 'passwordlama123',
        ]);

        $this->actingAs($admin)->put("/users/{$petugas->id}", [
            'username' => $petugas->username,
            'name' => $petugas->name,
            'email' => $petugas->email,
            'role' => $petugas->role->value,
            'password' => 'passwordbaru123',
            'password_confirmation' => 'passwordbaru123',
        ])->assertRedirect(route('users.index'));

        $this->post('/logout');

        $this->post('/login', [
            'username' => 'petugas_lama',
            'password' => 'passwordbaru123',
        ])->assertRedirect(route('dashboard'));
    }
}
