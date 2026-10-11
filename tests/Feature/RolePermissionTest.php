<?php

namespace Tests\Feature;

use App\Models\Product;
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

        $this->actingAs($petugas)->get('/products/create')->assertForbidden();
        $this->actingAs($petugas)->post('/products', [
            'sku' => 'TLR-BARU-01',
            'name' => 'Telur Asin Baru',
            'category' => 'Original',
            'price' => 3500,
            'stock' => 10,
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

        $this->actingAs($admin)->post('/products', [
            'sku' => 'TLR-BARU-01',
            'name' => 'Telur Asin Baru',
            'category' => 'Original',
            'price' => 3500,
            'stock' => 10,
            'min_stock' => 5,
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['sku' => 'TLR-BARU-01']);
    }
}
