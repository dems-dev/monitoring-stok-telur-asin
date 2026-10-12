<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

/**
 * Satu proses pekerja untuk stok:uji-konkurensi. Tidak untuk dipanggil langsung.
 * Mencetak OK (barang keluar tersimpan) atau DITOLAK (stok tidak cukup).
 */
class UjiKonkurensiStokPekerja extends Command
{
    protected $signature = 'stok:uji-konkurensi-pekerja
        {product : ID produk uji}
        {jumlah : Jumlah barang keluar}
        {user : ID user pencatat}
        {--mulai= : Waktu mulai serentak (unix timestamp, desimal)}
        {--delay=200 : Jeda (ms) antara membaca dan menulis stok}
        {--tanpa-kunci : Kurangi stok tanpa transaksi & lockForUpdate}';

    protected $description = 'Pekerja internal untuk stok:uji-konkurensi';

    protected $hidden = true;

    public function handle(StockService $stockService): int
    {
        $jumlah = (int) $this->argument('jumlah');
        $delayMikrodetik = (int) $this->option('delay') * 1000;

        // Jeda disisipkan tepat sebelum UPDATE stok, yaitu sesudah stok dibaca.
        // Pada StockService, baris produk saat itu sudah dikunci lockForUpdate.
        Product::updating(fn () => usleep($delayMikrodetik));

        while (microtime(true) < (float) $this->option('mulai')) {
            usleep(1000);
        }

        if ($this->option('tanpa-kunci')) {
            // Pola yang SALAH (hanya sebagai pembanding): baca-cek-tulis tanpa kunci.
            $product = Product::findOrFail($this->argument('product'));
            if ($product->stock < $jumlah) {
                $this->line('DITOLAK');

                return self::SUCCESS;
            }
            $product->stock -= $jumlah;
            $product->save();
            $this->line('OK');

            return self::SUCCESS;
        }

        try {
            $stockService->barangKeluar(
                Product::findOrFail($this->argument('product')),
                $jumlah,
                ['source_or_target' => 'Uji Konkurensi', 'status' => 'Terjual'],
                User::findOrFail($this->argument('user')),
            );
            $this->line('OK');
        } catch (ValidationException) {
            $this->line('DITOLAK');
        }

        return self::SUCCESS;
    }
}
