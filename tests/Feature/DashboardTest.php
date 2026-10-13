<?php

namespace Tests\Feature;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_bisa_diakses_oleh_admin_petugas_dan_pemilik(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $owner = User::factory()->owner()->create();

        $this->actingAs($admin)->get('/dashboard')->assertOk();
        $this->actingAs($petugas)->get('/dashboard')->assertOk();
        $this->actingAs($owner)->get('/dashboard')->assertOk();
    }

    public function test_nilai_persediaan_hanya_tampil_untuk_admin_dan_pemilik(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->create();
        $owner = User::factory()->owner()->create();

        Product::factory()->create([
            'name' => 'Telur Rebus Super',
            'stock' => 100,
            'purchase_price' => 3000,
        ]);

        // Admin dan Owner dapat melihat Nilai Persediaan
        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Nilai Persediaan')
            ->assertSee('Rp 300.000');

        $this->actingAs($owner)->get('/dashboard')
            ->assertOk()
            ->assertSee('Nilai Persediaan')
            ->assertSee('Rp 300.000');

        // Petugas tidak boleh melihat Nilai Persediaan
        $this->actingAs($petugas)->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Nilai Persediaan');
    }

    public function test_nilai_persediaan_tidak_menaksir_produk_tanpa_harga_beli(): void
    {
        $admin = User::factory()->admin()->create();

        Product::factory()->create(['stock' => 100, 'purchase_price' => 3000]);
        // Data lama tanpa harga beli: dulu ditaksir 80% harga jual, sekarang tidak dihitung.
        Product::factory()->create(['stock' => 50, 'price' => 5000, 'purchase_price' => 0]);

        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $this->assertSame(300000, $response->viewData('totalInventoryValue'));
        $response->assertSee('Rp 300.000')
            ->assertSee('1 produk belum ada harga beli (tidak dihitung)');
    }

    public function test_banner_stok_menipis_tampil_saat_ada_produk_mencapai_batas_minimum(): void
    {
        $petugas = User::factory()->create();

        Product::factory()->create([
            'name' => 'Telur Asin Asap Langka',
            'stock' => 10,
            'min_stock' => 25,
        ]);

        $this->actingAs($petugas)->get('/dashboard')
            ->assertOk()
            ->assertSee('Peringatan Stok Menipis')
            ->assertSee('Telur Asin Asap Langka');
    }

    public function test_banner_kedaluwarsa_tampil_saat_ada_produk_mendekati_kedaluwarsa(): void
    {
        $admin = User::factory()->admin()->create();

        Product::factory()->create([
            'name' => 'Telur Asin Rasa Bawang',
            'stock' => 50,
            'nearest_expiry_date' => today()->addDays(2),
        ]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Peringatan Produk Mendekati Kedaluwarsa')
            ->assertSee('Telur Asin Rasa Bawang');
    }

    public function test_mutasi_masuk_dan_keluar_hari_ini_ditampilkan_terpisah(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        StockLog::factory()->create([
            'product_id' => $product->id,
            'type' => LogType::MASUK,
            'amount' => 150,
            'logged_date' => today(),
        ]);

        StockLog::factory()->create([
            'product_id' => $product->id,
            'type' => LogType::KELUAR,
            'amount' => 60,
            'logged_date' => today(),
        ]);

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $response->assertSee('Masuk Hari Ini');
        $response->assertSee('Keluar Hari Ini');
        $response->assertSee('150 '.($product->unit?->name ?? 'butir'));
        $response->assertSee('60 '.($product->unit?->name ?? 'butir'));
    }

    public function test_grafik_tren_30_hari_berisi_total_masuk_dan_keluar_per_tanggal(): void
    {
        $admin = User::factory()->admin()->create();
        $product = Product::factory()->create();

        StockLog::factory()->create(['product_id' => $product->id, 'type' => LogType::MASUK, 'amount' => 100, 'logged_date' => today()]);
        StockLog::factory()->create(['product_id' => $product->id, 'type' => LogType::MASUK, 'amount' => 20, 'logged_date' => today()]);
        StockLog::factory()->create(['product_id' => $product->id, 'type' => LogType::KELUAR, 'amount' => 35, 'logged_date' => today()->subDays(3)]);
        // Di luar rentang 30 hari, tidak boleh ikut terhitung.
        StockLog::factory()->create(['product_id' => $product->id, 'type' => LogType::MASUK, 'amount' => 999, 'logged_date' => today()->subDays(40)]);

        $response = $this->actingAs($admin)->get('/dashboard')->assertOk();

        $trend = $response->viewData('charts')['trend'];

        $this->assertCount(30, $trend['labels']);
        // Indeks 29 = hari ini, indeks 26 = tiga hari lalu.
        $this->assertSame(120, $trend['masuk'][29]);
        $this->assertSame(0, $trend['keluar'][29]);
        $this->assertSame(35, $trend['keluar'][26]);
        $this->assertSame(120, array_sum($trend['masuk']));
        $this->assertSame(35, array_sum($trend['keluar']));
    }

    public function test_view_composer_menyediakan_jumlah_stok_menipis_di_header(): void
    {
        $admin = User::factory()->admin()->create();

        // 2 produk di bawah batas minimum
        Product::factory()->count(2)->lowStock()->create();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertOk();
        $response->assertViewHas('lowStockCount', 2);
    }
}
