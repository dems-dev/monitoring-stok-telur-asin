<?php

namespace Tests\Feature;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_barang_masuk_menambah_stok_dan_mencatat_log(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $response = $this->actingAs($user)->post('/barang-masuk', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 50,
            'source_or_target' => 'Dapur Utama Brebes',
            'note' => 'Panen pengasinan',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(150, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => LogType::MASUK->value,
            'amount' => 50,
            'status' => 'Selesai',
        ]);
    }

    public function test_barang_keluar_mengurangi_stok(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $this->actingAs($user)->post('/barang-keluar', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 40,
            'status' => 'Terjual',
            'source_or_target' => 'Toko Oleh-Oleh Cabang 1',
        ])->assertRedirect(route('dashboard'));

        $this->assertSame(60, $product->fresh()->stock);
    }

    public function test_barang_keluar_ditolak_bila_stok_tidak_cukup(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 10]);

        $response = $this->actingAs($user)->post('/barang-keluar', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 11,
            'status' => 'Terjual',
            'source_or_target' => 'Toko Oleh-Oleh Cabang 1',
        ]);

        $response->assertSessionHasErrors('amount');

        // Stok tidak boleh berubah dan tidak ada log yang tertinggal.
        $this->assertSame(10, $product->fresh()->stock);
        $this->assertSame(0, StockLog::count());
    }

    public function test_status_mutasi_masuk_diisi_server_meski_klien_mengirim_lain(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 0]);

        $this->actingAs($user)->post('/barang-masuk', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 5,
            'source_or_target' => 'Dapur Utama Brebes',
            'status' => 'Terjual',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'status' => 'Selesai',
        ]);
    }

    public function test_tanggal_masa_depan_ditolak(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)->post('/barang-masuk', [
            'logged_date' => today()->addDay()->toDateString(),
            'product_id' => $product->id,
            'amount' => 5,
            'source_or_target' => 'Dapur Utama Brebes',
        ])->assertSessionHasErrors('logged_date');
    }
}
