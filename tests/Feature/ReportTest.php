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

        // fputcsv menggandakan tanda kutip di dalam kolom; prototipe tidak.
        $this->assertStringContainsString('"Pembeli bilang ""retak"", minta ganti"', $csv);
    }
}
