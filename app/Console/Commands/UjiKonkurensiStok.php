<?php

namespace App\Console\Commands;

use App\Enums\LogType;
use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Unit;
use App\Models\User;
use App\Services\StockService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

/**
 * Bukti empiris bahwa StockService aman dari race condition: beberapa proses
 * PHP terpisah serentak mengurangi stok produk yang sama di MySQL/MariaDB.
 * Test PHPUnit tidak bisa membuktikan ini karena memakai SQLite, yang
 * mengabaikan lockForUpdate().
 *
 * Contoh: php artisan stok:uji-konkurensi
 *         php artisan stok:uji-konkurensi --tanpa-kunci   (pembanding tanpa kunci)
 */
class UjiKonkurensiStok extends Command
{
    protected $signature = 'stok:uji-konkurensi
        {--proses=10 : Jumlah proses yang berjalan serentak}
        {--jumlah=10 : Jumlah barang keluar per proses}
        {--stok=50 : Stok awal produk uji}
        {--delay=200 : Jeda (ms) antara membaca dan menulis stok, untuk memperlebar peluang tabrakan}
        {--tanpa-kunci : Pembanding: kurangi stok tanpa transaksi & lockForUpdate}
        {--simpan : Jangan hapus produk uji setelah selesai}';

    protected $description = 'Uji race condition barang keluar dengan beberapa proses paralel (khusus MySQL/MariaDB)';

    public function handle(StockService $stockService): int
    {
        if (DB::getDriverName() === 'sqlite') {
            $this->error('SQLite tidak mendukung SELECT ... FOR UPDATE. Jalankan perintah ini dengan MySQL/MariaDB.');

            return self::FAILURE;
        }

        $proses = max(2, (int) $this->option('proses'));
        $jumlah = max(1, (int) $this->option('jumlah'));
        $stokAwal = max(0, (int) $this->option('stok'));
        $delay = max(0, (int) $this->option('delay'));
        $tanpaKunci = (bool) $this->option('tanpa-kunci');

        $admin = User::where('role', Role::ADMIN)->where('is_active', true)->firstOrFail();

        $product = Product::create([
            'sku' => 'UJI-KONKURENSI-'.now()->format('YmdHis'),
            'name' => 'Produk Uji Konkurensi (sementara)',
            'category_id' => Category::query()->value('id'),
            'unit_id' => Unit::query()->value('id'),
            'price' => 1,
            'purchase_price' => 1,
            'stock' => 0,
            'min_stock' => 0,
        ]);

        try {
            if ($stokAwal > 0) {
                $stockService->barangMasuk($product, $stokAwal, ['note' => 'Stok awal uji konkurensi'], $admin);
            }

            $this->info(sprintf(
                'Mode: %s | %d proses x %d butir, stok awal %d, jeda %d ms',
                $tanpaKunci ? 'TANPA KUNCI (pembanding)' : 'StockService (transaksi + lockForUpdate)',
                $proses, $jumlah, $stokAwal, $delay,
            ));

            $hasil = $this->jalankanPekerja($product, $admin, $proses, $jumlah, $delay, $tanpaKunci);

            return $this->laporkan($product->fresh(), $hasil, $stokAwal, $jumlah, $tanpaKunci);
        } finally {
            if (! $this->option('simpan')) {
                StockLog::where('product_id', $product->id)->delete();
                $product->forceDelete();
                $this->line('Produk uji dan riwayatnya sudah dihapus.');
            }
        }
    }

    /** @return array<int, string> keluaran terakhir tiap proses: OK / DITOLAK / ERROR */
    private function jalankanPekerja(Product $product, User $admin, int $proses, int $jumlah, int $delay, bool $tanpaKunci): array
    {
        // Semua proses menunggu titik waktu yang sama agar benar-benar serentak.
        $mulai = microtime(true) + 3;

        $pekerja = collect(range(1, $proses))->map(function () use ($product, $admin, $jumlah, $delay, $tanpaKunci, $mulai) {
            $process = new Process(array_values(array_filter([
                PHP_BINARY, base_path('artisan'), 'stok:uji-konkurensi-pekerja',
                $product->id, $jumlah, $admin->id,
                '--mulai='.$mulai,
                '--delay='.$delay,
                $tanpaKunci ? '--tanpa-kunci' : null,
            ])), base_path(), null, null, 120);

            $process->start();

            return $process;
        });

        return $pekerja->map(function (Process $process) {
            $process->wait();
            $baris = array_filter(explode("\n", trim($process->getOutput())));

            return $process->isSuccessful() ? (string) end($baris) : 'ERROR: '.trim($process->getErrorOutput());
        })->all();
    }

    /** @param array<int, string> $hasil */
    private function laporkan(Product $product, array $hasil, int $stokAwal, int $jumlah, bool $tanpaKunci): int
    {
        $berhasil = count(array_filter($hasil, fn ($h) => $h === 'OK'));
        $ditolak = count(array_filter($hasil, fn ($h) => $h === 'DITOLAK'));
        $error = array_values(array_filter($hasil, fn ($h) => str_starts_with($h, 'ERROR')));

        $stokSeharusnya = $stokAwal - ($berhasil * $jumlah);
        $logKeluar = StockLog::where('product_id', $product->id)->where('type', LogType::KELUAR)->orderBy('id')->get();

        // Rantai snapshot: stok_sebelum tiap log harus sama dengan stok_sesudah log sebelumnya.
        $rantaiSnapshotUtuh = true;
        $sebelumnya = $stokAwal;
        foreach ($logKeluar as $log) {
            if ($log->stok_sebelum !== $sebelumnya) {
                $rantaiSnapshotUtuh = false;
            }
            $sebelumnya = $log->stok_sesudah;
        }

        $this->table(['Pemeriksaan', 'Hasil'], [
            ['Proses berhasil mengeluarkan barang', $berhasil],
            ['Proses ditolak (stok tidak cukup)', $ditolak],
            ['Total barang yang tercatat keluar', ($berhasil * $jumlah).' butir'],
            ['Stok seharusnya (awal - total keluar)', $stokSeharusnya],
            ['Stok di database', $product->stock],
            ['Rantai snapshot stok_sebelum/sesudah', $tanpaKunci ? '- (tidak dicatat)' : ($rantaiSnapshotUtuh ? 'utuh' : 'PUTUS')],
        ]);

        foreach ($error as $pesan) {
            $this->error($pesan);
        }

        $konsisten = $error === []
            && $product->stock === $stokSeharusnya
            && $product->stock >= 0
            && ($tanpaKunci || ($logKeluar->count() === $berhasil && $rantaiSnapshotUtuh));

        if ($konsisten) {
            $this->info('KONSISTEN: stok di database sama persis dengan jumlah transaksi yang berhasil.');

            return self::SUCCESS;
        }

        $this->error(sprintf(
            'TIDAK KONSISTEN: %d butir tercatat keluar, tetapi stok hanya berkurang %d (selisih %d butir "hilang").',
            $berhasil * $jumlah, $stokAwal - $product->stock, $product->stock - $stokSeharusnya,
        ));

        return self::FAILURE;
    }
}
