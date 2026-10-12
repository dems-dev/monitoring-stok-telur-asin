<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_stok_produk_hasil_seeder_cocok_dengan_snapshot_mutasi_terakhir(): void
    {
        $this->seed();

        $expected = [
            'TLR-ORIG-01' => 450,
            'TLR-BAKAR-02' => 35,
            'TLR-ASAP-03' => 120,
            'TLR-MASIR-04' => 12,
            'TLR-PAKET-06' => 80,
            'TLR-MNTH-05' => 280,
        ];

        foreach ($expected as $sku => $stok) {
            $product = Product::where('sku', $sku)->firstOrFail();
            $lastLog = StockLog::where('product_id', $product->id)->latest('logged_date')->latest('id')->firstOrFail();

            $this->assertSame($stok, $product->stock, "Stok {$sku} tidak sesuai.");
            $this->assertSame($stok, $lastLog->stok_sesudah, "Snapshot terakhir {$sku} tidak sesuai stok produk.");
        }
    }

    public function test_kartu_stok_data_demo_saldo_akhirnya_sama_dengan_stok_produk(): void
    {
        $this->seed();

        $admin = User::where('username', 'admin')->firstOrFail();
        $product = Product::where('sku', 'TLR-ORIG-01')->firstOrFail();

        $summary = $this->actingAs($admin)
            ->get(route('reports.stock_card', ['product_id' => $product->id]))
            ->assertOk()
            ->viewData('summary');

        $this->assertSame(0, $summary['initial_stock']);
        $this->assertSame(450, $summary['total_masuk']);
        $this->assertSame(450, $summary['final_stock']);
    }

    public function test_menjalankan_seeder_dua_kali_tidak_menggandakan_stok(): void
    {
        $this->seed();
        $this->seed();

        $this->assertSame(450, Product::where('sku', 'TLR-ORIG-01')->value('stock'));
        $this->assertSame(9, StockLog::count());
    }
}
