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
            'stok_sebelum' => 100,
            'stok_sesudah' => 150,
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

        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => LogType::KELUAR->value,
            'amount' => 40,
            'stok_sebelum' => 100,
            'stok_sesudah' => 60,
            'status' => 'Terjual',
        ]);
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

    public function test_stock_opname_menyesuaikan_stok_fisik_dan_mencatat_snapshot_saat_selisih_kurang(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 50]);

        $response = $this->actingAs($user)->post('/stock-opname', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'stok_fisik' => 47,
            'alasan' => 'Ditemukan 3 butir pecah di rak penyimpanan.',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(47, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => LogType::PENYESUAIAN->value,
            'amount' => 3,
            'stok_sebelum' => 50,
            'stok_sesudah' => 47,
            'status' => 'Selisih Kurang',
            'note' => 'Ditemukan 3 butir pecah di rak penyimpanan.',
        ]);
    }

    public function test_stock_opname_menyesuaikan_stok_fisik_dan_mencatat_snapshot_saat_selisih_lebih(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 40]);

        $response = $this->actingAs($user)->post('/stock-opname', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'stok_fisik' => 45,
            'alasan' => 'Kelebihan pasokan panen belum terinput.',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(45, $product->fresh()->stock);

        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'user_id' => $user->id,
            'type' => LogType::PENYESUAIAN->value,
            'amount' => 5,
            'stok_sebelum' => 40,
            'stok_sesudah' => 45,
            'status' => 'Selisih Lebih',
        ]);
    }

    public function test_stock_opname_wajib_mengisi_alasan_dan_stok_fisik_tidak_boleh_negatif(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 50]);

        // Alasan kosong harus ditolak
        $response = $this->actingAs($user)->post('/stock-opname', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'stok_fisik' => 48,
            'alasan' => '',
        ]);
        $response->assertSessionHasErrors('alasan');

        // Stok fisik negatif harus ditolak
        $response = $this->actingAs($user)->post('/stock-opname', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'stok_fisik' => -5,
            'alasan' => 'Koreksi stok minus',
        ]);
        $response->assertSessionHasErrors('stok_fisik');
    }

    public function test_pemilik_ditolak_403_saat_mengakses_semua_halaman_transaksi_stok(): void
    {
        $owner = User::factory()->owner()->create();
        $product = Product::factory()->create(['stock' => 20]);

        // Form routes
        $this->actingAs($owner)->get('/barang-masuk')->assertForbidden();
        $this->actingAs($owner)->get('/barang-keluar')->assertForbidden();
        $this->actingAs($owner)->get('/stock-opname')->assertForbidden();

        // Submit routes
        $this->actingAs($owner)->post('/barang-masuk', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 10,
            'source_or_target' => 'Gudang',
        ])->assertForbidden();

        $this->actingAs($owner)->post('/barang-keluar', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 5,
            'status' => 'Terjual',
            'source_or_target' => 'Toko',
        ])->assertForbidden();

        $this->actingAs($owner)->post('/stock-opname', [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'stok_fisik' => 15,
            'alasan' => 'Opname owner',
        ])->assertForbidden();
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

    public function test_tanggal_masa_depan_ditolak_pada_semua_transaksi(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $tomorrow = today()->addDay()->toDateString();

        $this->actingAs($user)->post('/barang-masuk', [
            'logged_date' => $tomorrow,
            'product_id' => $product->id,
            'amount' => 5,
            'source_or_target' => 'Dapur Utama Brebes',
        ])->assertSessionHasErrors('logged_date');

        $this->actingAs($user)->post('/barang-keluar', [
            'logged_date' => $tomorrow,
            'product_id' => $product->id,
            'amount' => 5,
            'status' => 'Terjual',
            'source_or_target' => 'Toko',
        ])->assertSessionHasErrors('logged_date');

        $this->actingAs($user)->post('/stock-opname', [
            'logged_date' => $tomorrow,
            'product_id' => $product->id,
            'stok_fisik' => 18,
            'alasan' => 'Opname besok',
        ])->assertSessionHasErrors('logged_date');
    }
}
