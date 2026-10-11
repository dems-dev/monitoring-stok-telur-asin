<?php

namespace Database\Seeders;

use App\Enums\LogType;
use App\Enums\Role;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class DatabaseSeeder extends Seeder
{
    /**
     * Data contoh dipindahkan apa adanya dari prototipe di docs/prototype/index.html
     * (DEFAULT_ACCOUNTS, INITIAL_PRODUCTS, INITIAL_LOGS).
     *
     * Catatan: nilai stok produk di bawah adalah data contoh yang berdiri sendiri,
     * bukan hasil penjumlahan stock_logs — sama seperti di prototipe.
     */
    public function run(): void
    {
        $users = collect([
            ['username' => 'admin', 'name' => 'Budi Santoso', 'email' => 'admin@telur.com', 'role' => Role::ADMIN, 'password' => 'admin123'],
            ['username' => 'petugas', 'name' => 'Siti Rahma', 'email' => 'petugas@telur.com', 'role' => Role::PETUGAS, 'password' => 'petugas123'],
            ['username' => 'owner', 'name' => 'H. Ahmad Brebes', 'email' => 'owner@telur.com', 'role' => Role::OWNER, 'password' => 'owner123'],
        ])->mapWithKeys(fn (array $data) => [
            $data['username'] => User::updateOrCreate(['username' => $data['username']], $data),
        ]);

        $products = collect([
            ['sku' => 'TLR-ORIG-01', 'name' => 'Telur Asin Original Rebus', 'category' => 'Original', 'price' => 3500, 'stock' => 450, 'min_stock' => 100],
            ['sku' => 'TLR-BAKAR-02', 'name' => 'Telur Asin Bakar Rempah', 'category' => 'Bakar', 'price' => 4000, 'stock' => 35, 'min_stock' => 50],
            ['sku' => 'TLR-ASAP-03', 'name' => 'Telur Asin Asap Premium', 'category' => 'Asap', 'price' => 4500, 'stock' => 120, 'min_stock' => 40],
            ['sku' => 'TLR-MASIR-04', 'name' => 'Telur Asin Bumbu Masir Super', 'category' => 'Bumbu/Rasa', 'price' => 4200, 'stock' => 12, 'min_stock' => 30],
            ['sku' => 'TLR-PAKET-06', 'name' => 'Telur Asin Pack Isi 6', 'category' => 'Kemasan', 'price' => 22000, 'stock' => 80, 'min_stock' => 25],
            ['sku' => 'TLR-MNTH-05', 'name' => 'Telur Mentah Salted Batch A', 'category' => 'Mentah', 'price' => 3000, 'stock' => 280, 'min_stock' => 80],
        ])->mapWithKeys(fn (array $data) => [
            $data['sku'] => Product::updateOrCreate(['sku' => $data['sku']], $data),
        ]);

        $logs = [
            [
                'type' => LogType::MASUK,
                'product_id' => $products['TLR-ORIG-01']->id,
                'user_id' => $users['petugas']->id,
                'amount' => 200,
                'source_or_target' => 'Dapur Utama Brebes',
                'status' => 'Selesai',
                'note' => 'Panen Hasil Pengasinan',
                'logged_date' => Carbon::today()->subDays(2),
            ],
            [
                'type' => LogType::KELUAR,
                'product_id' => $products['TLR-BAKAR-02']->id,
                'user_id' => $users['petugas']->id,
                'amount' => 50,
                'source_or_target' => 'Toko Oleh-Oleh Cabang 1',
                'status' => 'Terjual',
                'note' => 'Pengiriman Penjualan',
                'logged_date' => Carbon::today()->subDay(),
            ],
            [
                'type' => LogType::KELUAR,
                'product_id' => $products['TLR-MASIR-04']->id,
                'user_id' => $users['admin']->id,
                'amount' => 10,
                'source_or_target' => 'Pengujian Mutu (QC)',
                'status' => 'Rusak/Pecah',
                'note' => 'Retak saat sortir',
                'logged_date' => Carbon::today(),
            ],
        ];

        foreach ($logs as $log) {
            StockLog::firstOrCreate(
                [
                    'product_id' => $log['product_id'],
                    'logged_date' => $log['logged_date'],
                    'type' => $log['type'],
                    'amount' => $log['amount'],
                ],
                $log
            );
        }
    }
}
