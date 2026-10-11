# SIM-STOK TELUR ASIN

Sistem Monitoring Persediaan Stok Telur Asin — Laravel 12 + MariaDB/MySQL.

Dibangun ulang dari prototipe satu berkas HTML yang diarsipkan di
`docs/prototype/index.html`. Prototipe itu menyimpan seluruh data di
`localStorage`; versi ini memakai database, otorisasi di server, dan
pencatatan mutasi yang aman terhadap entri bersamaan.

## Kebutuhan

| Komponen | Versi yang dipakai saat dibangun |
|---|---|
| PHP | 8.4.25 (ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `intl`, `zip`) |
| Database | MariaDB 10.4.32 bawaan XAMPP di `127.0.0.1:3306`, root tanpa password (MySQL 8 juga jalan; cukup ubah `DB_PASSWORD`) |
| Node | 22.x + npm 10.x |
| Composer | 2.8.x |

Untuk menjalankan test dibutuhkan `pdo_sqlite` (phpunit memakai SQLite
`:memory:`). Pada instalasi PHP dari winget, berkas `php_pdo_sqlite.dll`
sudah tersedia di folder `ext/` tetapi barisnya dinonaktifkan di `php.ini`.

## Penyiapan

```bash
composer install
npm install

# Nyalakan MariaDB lewat XAMPP Control Panel, lalu buat databasenya:
#   CREATE DATABASE sim_telur_asin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
# Sesuaikan DB_PASSWORD di .env bila database Anda berpassword.
php artisan key:generate   # hanya jika .env belum punya APP_KEY

php artisan migrate --seed
npm run build
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Akun contoh dari seeder (ganti sebelum dipakai sungguhan):

| Username | Password | Peran |
|---|---|---|
| `admin` | `admin123` | ADMIN — akses penuh |
| `petugas` | `petugas123` | PETUGAS — catat mutasi, lihat stok |
| `owner` | `owner123` | OWNER — hanya baca |

## Pembagian peran

| Kemampuan | ADMIN | PETUGAS | OWNER |
|---|:---:|:---:|:---:|
| Lihat dashboard, katalog, laporan | ya | ya | ya |
| Catat barang masuk / keluar | ya | ya | — |
| Kelola master produk | ya | — | — |
| Kelola pengguna | ya | — | — |

Aturannya terpusat di `App\Enums\Role`, ditegakkan oleh
`App\Policies\ProductPolicy` dan middleware `role:` di `routes/web.php`.

## Struktur data

- **products** — `sku` unik, kategori, harga, `stock`, `min_stock`.
  Dihapus lunak (*soft delete*) agar riwayat mutasi tidak ikut hilang.
- **stock_logs** — `type` (MASUK/KELUAR), FK produk dan pencatat, `amount`,
  `source_or_target`, `status`, `note`, `logged_date`.
  `logged_date` adalah tanggal yang diisi petugas; `created_at` waktu entri
  sebenarnya, sehingga keduanya bisa dibandingkan saat audit.

## Perbedaan yang disengaja dari prototipe

- **Stok tidak bisa minus.** Mutasi keluar dijalankan dalam satu transaksi
  dengan `lockForUpdate()` pada baris produk, jadi dua entri bersamaan tidak
  bisa menembus stok fisik. Prototipe memeriksa stok di sisi klien saja.
- **Status mutasi masuk diisi server**, bukan dikirim dari input klien.
- **Ekspor CSV mengikuti filter yang aktif.** Prototipe selalu mengekspor
  seluruh log meski filter terpasang.
- **CSV lewat `fputcsv` + BOM UTF-8.** Prototipe merangkai string tanpa
  *escaping*, sehingga catatan yang memuat tanda kutip merusak kolom.
- **Password di-hash.** Prototipe menyimpannya sebagai teks biasa di
  `localStorage`.

## Test

```bash
php artisan test
```

Mencakup login via username, batas tiap peran, penambahan dan pengurangan
stok, penolakan stok tidak cukup tanpa menyisakan log, status mutasi masuk
yang tidak bisa disetir klien, filter laporan, dan *escaping* CSV.
