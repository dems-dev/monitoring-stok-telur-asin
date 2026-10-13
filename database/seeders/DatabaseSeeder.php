<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect([
            ['username' => 'admin', 'name' => 'Budi Santoso', 'email' => 'admin@telur.com', 'role' => Role::ADMIN, 'password' => 'admin123'],
            ['username' => 'petugas', 'name' => 'Siti Rahma', 'email' => 'petugas@telur.com', 'role' => Role::PETUGAS, 'password' => 'petugas123'],
            ['username' => 'owner', 'name' => 'H. Ahmad Brebes', 'email' => 'owner@telur.com', 'role' => Role::OWNER, 'password' => 'owner123'],
        ])->mapWithKeys(fn (array $data) => [
            // firstOrCreate: seeder aman dijalankan ulang (mis. di setiap deploy)
            // tanpa menimpa password yang sudah diganti pengguna.
            $data['username'] => User::firstOrCreate(['username' => $data['username']], $data),
        ]);

        $units = collect([
            'butir' => Unit::firstOrCreate(['name' => 'butir']),
            'pack' => Unit::firstOrCreate(['name' => 'pack']),
        ]);

        $categories = collect([
            'Original' => Category::firstOrCreate(['name' => 'Original']),
            'Bakar' => Category::firstOrCreate(['name' => 'Bakar']),
            'Asap' => Category::firstOrCreate(['name' => 'Asap']),
            'Bumbu/Rasa' => Category::firstOrCreate(['name' => 'Bumbu/Rasa']),
            'Kemasan' => Category::firstOrCreate(['name' => 'Kemasan']),
            'Mentah' => Category::firstOrCreate(['name' => 'Mentah']),
        ]);

        $suppliers = collect([
            'dapur' => Supplier::firstOrCreate(
                ['name' => 'Dapur Utama Brebes'],
                ['phone' => '081234567891', 'address' => 'Jl. Pengasinan No. 5, Brebes']
            ),
            'peternak' => Supplier::firstOrCreate(
                ['name' => 'Peternak Bebek Brebes'],
                ['phone' => '081234567890', 'address' => 'Jl. Pantura No. 12, Brebes']
            ),
        ]);

        // Kolom stok sengaja tidak diisi: stok produk hanya boleh berubah lewat
        // StockService, termasuk saat seeding, supaya snapshot stok_sebelum /
        // stok_sesudah dan Kartu Stok selalu cocok dengan stok produk.
        $products = collect([
            ['sku' => 'TLR-ORIG-01', 'name' => 'Telur Asin Original Rebus', 'category_id' => $categories['Original']->id, 'unit_id' => $units['butir']->id, 'price' => 3500, 'purchase_price' => 2800, 'min_stock' => 100, 'nearest_expiry_date' => Carbon::today()->addDays(14)],
            ['sku' => 'TLR-BAKAR-02', 'name' => 'Telur Asin Bakar Rempah', 'category_id' => $categories['Bakar']->id, 'unit_id' => $units['butir']->id, 'price' => 4000, 'purchase_price' => 3200, 'min_stock' => 50, 'nearest_expiry_date' => Carbon::today()->addDays(7)],
            ['sku' => 'TLR-ASAP-03', 'name' => 'Telur Asin Asap Premium', 'category_id' => $categories['Asap']->id, 'unit_id' => $units['butir']->id, 'price' => 4500, 'purchase_price' => 3600, 'min_stock' => 40, 'nearest_expiry_date' => Carbon::today()->addDays(10)],
            ['sku' => 'TLR-MASIR-04', 'name' => 'Telur Asin Bumbu Masir Super', 'category_id' => $categories['Bumbu/Rasa']->id, 'unit_id' => $units['butir']->id, 'price' => 4200, 'purchase_price' => 3300, 'min_stock' => 30, 'nearest_expiry_date' => Carbon::today()->addDays(2)],
            ['sku' => 'TLR-PAKET-06', 'name' => 'Telur Asin Pack Isi 6', 'category_id' => $categories['Kemasan']->id, 'unit_id' => $units['pack']->id, 'price' => 22000, 'purchase_price' => 17500, 'min_stock' => 25, 'nearest_expiry_date' => Carbon::today()->addDays(20)],
            ['sku' => 'TLR-MNTH-05', 'name' => 'Telur Mentah Salted Batch A', 'category_id' => $categories['Mentah']->id, 'unit_id' => $units['butir']->id, 'price' => 3000, 'purchase_price' => 2400, 'min_stock' => 80, 'nearest_expiry_date' => Carbon::today()->addDays(5)],
        ])->mapWithKeys(fn (array $data) => [
            // withTrashed + firstOrCreate: tidak menimpa harga yang sudah diubah admin
            // dan tidak bentrok SKU unik bila produk demo sudah dihapus lunak.
            $data['sku'] => Product::withTrashed()->firstOrCreate(['sku' => $data['sku']], $data),
        ]);

        // Mutasi hanya dibuat sekali; menjalankan seeder ulang tidak menggandakan stok.
        if (StockLog::exists()) {
            return;
        }

        $stock = app(StockService::class);
        $petugas = $users['petugas'];
        $admin = $users['admin'];

        // Stok awal seminggu lalu. Setelah mutasi di bawahnya, stok akhir menjadi:
        // ORIG 450, BAKAR 35, ASAP 120, MASIR 12, PAKET 80, MNTH 280.
        $stokAwal = [
            'TLR-ORIG-01' => 250,
            'TLR-BAKAR-02' => 85,
            'TLR-ASAP-03' => 120,
            'TLR-MASIR-04' => 22,
            'TLR-PAKET-06' => 80,
            'TLR-MNTH-05' => 280,
        ];

        foreach ($stokAwal as $sku => $jumlah) {
            $stock->barangMasuk($products[$sku], $jumlah, [
                'supplier_id' => $suppliers['dapur']->id,
                'source_or_target' => $suppliers['dapur']->name,
                'note' => 'Stok awal sistem',
                'logged_date' => Carbon::today()->subDays(7)->toDateString(),
            ], $admin);
        }

        $stock->barangMasuk($products['TLR-ORIG-01'], 200, [
            'supplier_id' => $suppliers['dapur']->id,
            'source_or_target' => $suppliers['dapur']->name,
            'note' => 'Panen Hasil Pengasinan',
            'logged_date' => Carbon::today()->subDays(2)->toDateString(),
        ], $petugas);

        $stock->barangKeluar($products['TLR-BAKAR-02'], 50, [
            'source_or_target' => 'Toko Oleh-Oleh Cabang 1',
            'status' => 'Terjual',
            'note' => 'Pengiriman Penjualan',
            'logged_date' => Carbon::today()->subDay()->toDateString(),
        ], $petugas);

        $stock->barangKeluar($products['TLR-MASIR-04'], 10, [
            'source_or_target' => 'Pengujian Mutu (QC)',
            'status' => 'Rusak/Pecah',
            'note' => 'Retak saat sortir',
            'logged_date' => Carbon::today()->toDateString(),
        ], $admin);
    }
}
