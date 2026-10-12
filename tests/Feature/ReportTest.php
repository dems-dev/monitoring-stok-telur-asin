<?php

namespace Tests\Feature;

use App\Enums\LogType;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_laporan_bisa_difilter_per_jenis_mutasi(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Telur Asin Original Rebus']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'source_or_target' => 'Dapur Utama Brebes',
        ]);
        StockLog::factory()->for($product)->create([
            'type' => LogType::KELUAR,
            'source_or_target' => 'Toko Oleh-Oleh Cabang 1',
        ]);

        $this->actingAs($user)
            ->get('/laporan?type=MASUK')
            ->assertOk()
            ->assertSee('Dapur Utama Brebes')
            ->assertDontSee('Toko Oleh-Oleh Cabang 1');
    }

    public function test_laporan_bisa_difilter_per_produk(): void
    {
        $user = User::factory()->create();
        $productA = Product::factory()->create(['name' => 'Telur Asin Panggang Super']);
        $productB = Product::factory()->create(['name' => 'Telur Asin Asap Rempah']);

        StockLog::factory()->for($productA)->create([
            'type' => LogType::MASUK,
            'amount' => 50,
        ]);
        StockLog::factory()->for($productB)->create([
            'type' => LogType::MASUK,
            'amount' => 75,
        ]);

        $response = $this->actingAs($user)
            ->get('/laporan?product_id=' . $productA->id);

        $response->assertOk();
        $this->assertCount(1, $response->viewData('logs'));
        $this->assertSame($productA->id, $response->viewData('logs')->first()->product_id);
    }

    public function test_laporan_bisa_difilter_per_petugas(): void
    {
        $userA = User::factory()->create(['name' => 'Petugas Anton']);
        $userB = User::factory()->create(['name' => 'Petugas Budi']);
        $product = Product::factory()->create();

        StockLog::factory()->for($product)->for($userA)->create([
            'source_or_target' => 'Pengiriman Oleh Anton',
        ]);
        StockLog::factory()->for($product)->for($userB)->create([
            'source_or_target' => 'Pengiriman Oleh Budi',
        ]);

        $this->actingAs($userA)
            ->get('/laporan?user_id=' . $userA->id)
            ->assertOk()
            ->assertSee('Pengiriman Oleh Anton')
            ->assertDontSee('Pengiriman Oleh Budi');
    }

    public function test_tanggal_akhir_sebelum_tanggal_mulai_ditolak(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/laporan?start_date=2026-05-10&end_date=2026-05-01')
            ->assertSessionHasErrors('end_date');
    }

    public function test_ekspor_csv_mengikuti_filter_yang_aktif(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['sku' => 'TLR-ORIG-01']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'source_or_target' => 'Dapur Utama Brebes',
        ]);
        StockLog::factory()->for($product)->create([
            'type' => LogType::KELUAR,
            'source_or_target' => 'Toko Oleh-Oleh Cabang 1',
        ]);

        $response = $this->actingAs($user)->get('/laporan/export?type=KELUAR');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Toko Oleh-Oleh Cabang 1', $csv);
        $this->assertStringNotContainsString('Dapur Utama Brebes', $csv);
    }

    public function test_csv_meng_escape_tanda_kutip_pada_catatan(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        StockLog::factory()->for($product)->create([
            'type' => LogType::KELUAR,
            'note' => 'Pembeli bilang "retak", minta ganti',
        ]);

        $csv = $this->actingAs($user)->get('/laporan/export')->streamedContent();

        $this->assertStringContainsString('"Pembeli bilang ""retak"", minta ganti"', $csv);
    }

    public function test_ekspor_pdf_berhasil_dan_mengembalikan_file_pdf(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Telur Asin Bumbu Masir']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'amount' => 100,
        ]);

        $response = $this->actingAs($user)->get('/laporan/export/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ekspor_excel_berhasil_dan_mengembalikan_file_excel(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'amount' => 100,
        ]);

        $response = $this->actingAs($user)->get('/laporan/export/excel');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_kartu_stok_menampilkan_buku_besar_dan_saldo_berjalan(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Telur Asin Original Rebus',
            'stock' => 120,
        ]);

        // Transaksi 1: Masuk 150 (stok 0 -> 150)
        StockLog::factory()->for($product)->for($user)->create([
            'type' => LogType::MASUK,
            'amount' => 150,
            'stok_sebelum' => 0,
            'stok_sesudah' => 150,
            'logged_date' => '2026-05-01',
        ]);

        // Transaksi 2: Keluar 30 (stok 150 -> 120)
        StockLog::factory()->for($product)->for($user)->create([
            'type' => LogType::KELUAR,
            'amount' => 30,
            'stok_sebelum' => 150,
            'stok_sesudah' => 120,
            'logged_date' => '2026-05-02',
        ]);

        $response = $this->actingAs($user)
            ->get('/laporan/kartu-stok?product_id=' . $product->id)
            ->assertOk()
            ->assertSee('Telur Asin Original Rebus')
            ->assertSee('150')
            ->assertSee('120');
    }

    public function test_ekspor_kartu_stok_pdf_berhasil(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['sku' => 'TLR-TEST-01']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'amount' => 50,
            'stok_sebelum' => 0,
            'stok_sesudah' => 50,
        ]);

        $response = $this->actingAs($user)->get('/laporan/kartu-stok/pdf?product_id=' . $product->id);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_ekspor_kartu_stok_excel_berhasil(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['sku' => 'TLR-TEST-01']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'amount' => 50,
            'stok_sebelum' => 0,
            'stok_sesudah' => 50,
        ]);

        $response = $this->actingAs($user)->get('/laporan/kartu-stok/excel?product_id=' . $product->id);

        $response->assertOk();
        $response->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
