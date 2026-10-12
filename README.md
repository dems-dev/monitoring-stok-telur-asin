# SIM-STOK Telur Asin

Sistem Monitoring Persediaan Barang Berbasis Web untuk Usaha Telur Asin.

Aplikasi ini dibangun menggunakan arsitektur modern **Laravel 12**, **MariaDB/MySQL**, **Tailwind CSS**, **Alpine.js**, dan **Chart.js** untuk menggantikan pencatatan manual maupun prototipe lama yang rentan data inkonsisten. Sistem dirancang khusus untuk memenuhi standar tugas akhir / skripsi dengan penekanan pada integritas data, pencegahan *race condition*, audit trail transaksi, kontrol akses berbasis peran (RBAC), serta asisten cerdas AI Chatbot persediaan berbasis *tool calling*.

---

## Daftar Isi

1. [Fitur Utama](#fitur-utama)
2. [Tech Stack](#tech-stack)
3. [Matriks Hak Akses (Role-Based Access Control)](#matriks-hak-akses-role-based-access-control)
4. [Alur Transaksi dan Integritas Stok](#alur-transaksi-dan-integritas-stok)
5. [Akun Demo](#akun-demo)
6. [Panduan Instalasi Lokal](#panduan-instalasi-lokal)
7. [Pengujian Otomatis (Testing)](#pengujian-otomatis-testing)
8. [Panduan Deploy ke Server Produksi (cPanel & VPS)](#panduan-deploy-ke-server-produksi-cpanel--vps)
9. [Struktur Direktori Proyek](#struktur-direktori-proyek)

---

## Fitur Utama

- **Integritas Stok & Anti-Race Condition**:
  - Nilai stok produk **hanya** dapat berubah melalui transaksi resmi (*Barang Masuk*, *Barang Keluar*, atau *Stock Opname*).
  - Admin maupun pengguna tidak dapat mengedit angka stok secara langsung di tabel produk.
  - Setiap perubahan stok dibungkus dalam `DB::transaction()` dengan penguncian baris `lockForUpdate()`.
  - Stok fisik tidak dapat bernilai negatif (validasi di sisi server menolak mutasi keluar yang melebihi ketersediaan stok).
  - Setiap mutasi mencatat snapshot `stok_sebelum`, `stok_sesudah`, dan `user_id` pembuat transaksi (*audit trail*).

- **Master Data Lengkap**:
  - Manajemen Produk (SKU unik, nama, kategori, satuan, harga beli, harga jual, stok minimum, estimasi tanggal kedaluwarsa terdekat).
  - Manajemen Kategori, Satuan (contoh: butir, pack, box), dan Supplier.
  - Proteksi *Soft Deletes* pada data produk agar riwayat mutasi masa lalu tetap utuh.
  - Validasi integritas: Kategori dan Satuan yang masih digunakan oleh produk tidak dapat dihapus.

- **Pencatatan Mutasi Stok**:
  - **Barang Masuk**: Penerimaan stok dari supplier / proses produksi dengan opsi pemilihan supplier dan catatan transaksi.
  - **Barang Keluar**: Pengurangan stok untuk penjualan, distribusi, telur pecah/rusak, atau kedaluwarsa.
  - **Stock Opname**: Penyesuaian stok fisik gudang dengan penghitungan selisih otomatis (+ / -), rekonsiliasi ke sistem, dan kewajiban menyertakan alasan penyesuaian.

- **Dashboard Monitoring Real-Time**:
  - Kartu statistik: Total Produk, Nilai Persediaan (Aset = Stok x Harga Beli, khusus Admin & Pemilik), Barang Masuk Hari Ini, Barang Keluar Hari Ini (terpisah), dan Jumlah Stok Menipis.
  - Banner peringatan dinamis: Produk yang mencapai/melewati batas minimum stok.
  - Banner peringatan kedaluwarsa: Produk yang mendekati masa kedaluwarsa (<= 3 hari).
  - Visualisasi Grafik Chart.js:
    1. Grafik Batang Stok per Produk dilengkapi garis ambang batas minimum.
    2. Grafik Garis Tren Barang Masuk vs Keluar selama 30 hari terakhir.
    3. Grafik Batang Horizontal 5 Produk Terlaris bulan berjalan.
  - Tabel 5 Transaksi Terakhir dengan penataan waktu kronologis.
  - Lonceng notifikasi pada header dengan badge jumlah produk stok menipis (ditenagai Laravel *View Composer*).

- **Laporan & Rekapitulasi Lanjutan**:
  - Filter riwayat mutasi berdasarkan jenis mutasi, produk, petugas pencatat, dan rentang tanggal.
  - Ekspor Laporan Riwayat Mutasi ke format **PDF** resmi (dilengkapi kop surat toko, periode, dan identitas pencetak).
  - Ekspor ke format **Excel (.xlsx)** dan **CSV (UTF-8 BOM)**.
  - **Buku Besar Kartu Stok per Produk**: Rekapitulasi saldo berjalan per tanggal (Stok Awal, Masuk, Keluar, Saldo Akhir) yang dapat diekspor langsung ke PDF dan Excel.

- **Manajemen Pengguna (User Management)**:
  - Kelola akun pengguna untuk peran Admin, Petugas, dan Pemilik.
  - Fitur reset password langsung oleh Admin (password baru di-hash bcrypt).
  - Mekanisme proteksi keamanan: Admin tidak dapat menghapus atau menonaktifkan akun miliknya sendiri, serta sistem mencegah penonaktifan akun Admin terakhir.

- **AI Chatbot Persediaan (Hanya-Baca)**:
  - Widget chat interaktif mengambang di pojok kanan bawah berbasis Alpine.js.
  - Terintegrasi dengan model AI melalui arsitektur *Tool Calling / Function Calling*:
    - `cek_stok(nama_produk)`
    - `daftar_stok_menipis()`
    - `produk_terlaris(periode)`
    - `riwayat_barang_masuk(nama_produk, jumlah_hari)`
  - Aman dan anti-halusinasi: AI hanya merangkum data riil dari database.
  - Bersifat hanya-baca (*read-only*), tidak memiliki akses untuk mengubah maupun menghapus data.
  - Dilengkapi *smart local fallback* jika API key LLM eksternal tidak dikonfigurasi.
  - Pencatatan riwayat percakapan pada tabel `chat_logs` dan proteksi *Rate Limiting* (maksimal 20 permintaan/menit per pengguna).

---

## Tech Stack

| Komponen | Teknologi | Keterangan |
|---|---|---|
| **Framework Backend** | Laravel 12 (PHP 8.3 / 8.4) | Arsitektur MVC, Form Request Validation, Eloquent ORM |
| **Database** | MariaDB 10.4+ / MySQL 8.0+ | Transaksi ACID, InnoDB engine, Foreign Keys |
| **Autentikasi** | Laravel Breeze (Blade Stack) | Login rate limiter, BCrypt hashing, sesi terproteksi |
| **Otorisasi / RBAC** | `spatie/laravel-permission` | Role & Policy middleware terpusat |
| **Styling & CSS** | Tailwind CSS v4 via Vite | Responsif (desktop, tablet, mobile), tema amber |
| **Interaktivitas UI** | Alpine.js | Navigasi responsif, modal interaktif, widget chatbot |
| **Visualisasi Grafik** | Chart.js | Grafik stok vs target, tren 30 hari, top terlaris |
| **Ikon UI** | Font Awesome 6 (SVG & CSS) | Ikon antarmuka modern |
| **Ekspor PDF** | `barryvdh/laravel-dompdf` | Format kop resmi, tabel presisi, ramah cetak A4 |
| **Ekspor Excel** | `maatwebsite/excel` | Spreadsheet berekstensi .xlsx dengan format angka rapi |
| **Testing** | PHPUnit / Laravel Test | 70 Feature Tests (244 assertions lolos 100%) |

---

## Matriks Hak Akses (Role-Based Access Control)

Hak akses ditegakkan di sisi server melalui middleware route (`role:ADMIN`, `role:ADMIN,PETUGAS`) dan Laravel Policy (`ProductPolicy`), bukan sekadar menyembunyikan elemen tombol di tampilan.

| Fitur / Modul | ADMIN | PETUGAS | PEMILIK (OWNER) |
|---|:---:|:---:|:---:|
| Lihat Dashboard & Grafik Monitoring | Ya | Ya | Ya |
| Lihat Nilai Persediaan Aset (Rupiah) | Ya | Tidak | Ya |
| Lihat Katalog Produk & Stok | Ya | Ya | Ya |
| Kelola Master Produk (Tambah, Edit, Hapus) | Ya | Tidak | Tidak |
| Kelola Kategori, Satuan, Supplier | Ya | Tidak | Tidak |
| Input Transaksi Barang Masuk | Ya | Ya | Tidak |
| Input Transaksi Barang Keluar | Ya | Ya | Tidak |
| Eksekusi Stock Opname (Penyesuaian Fisik) | Ya | Ya | Tidak |
| Lihat Laporan Riwayat Mutasi & Filter | Ya | Ya | Ya |
| Ekspor Laporan (PDF, Excel, CSV) | Ya | Ya | Ya |
| Lihat & Ekspor Kartu Stok per Produk | Ya | Ya | Ya |
| Kelola Pengguna (Tambah, Edit, Reset Password) | Ya | Tidak | Tidak |
| Akses AI Chatbot Persediaan | Ya | Ya | Ya |

---

## Alur Transaksi dan Integritas Stok

### 1. Alur Barang Masuk
Petugas/Admin menerima stok telur asin dari supplier atau dapur produksi -> Membuka menu **Barang Masuk** -> Memilih produk, tanggal penerimaan, supplier, jumlah masuk, dan catatan -> Sistem menjalankan `StockService::barangMasuk()` -> Stok produk terkunci (`lockForUpdate`) -> Stok bertambah -> Snapshot sebelum dan sesudah tersimpan di `stock_movements`.

### 2. Alur Barang Keluar
Petugas/Admin mencatat pengeluaran stok -> Membuka menu **Barang Keluar** -> Memilih produk, jumlah keluar, kategori keluar (*terjual*, *distribusi*, *rusak*, atau *kedaluwarsa*), tujuan pengiriman, dan tanggal -> Sistem mengecek ketersediaan stok fisik -> Jika stok tidak cukup, sistem langsung menolak dan membatalkan transaksi -> Jika cukup, stok berkurang dan snapshot tersimpan.

### 3. Alur Stock Opname
Petugas/Admin melakukan penghitungan fisik rutin di gudang -> Membuka menu **Stock Opname** -> Memilih produk -> Sistem menampilkan stok menurut sistem -> Pengguna memasukkan hasil hitung fisik riil -> Sistem menampilkan selisih secara otomatis -> Pengguna wajib menuliskan alasan penyesuaian -> Sistem menyesuaikan angka stok produk ke angka fisik riil dan mencatat riwayat penyesuaian.

### 4. Alur Buku Besar Kartu Stok
Pengguna membuka menu **Laporan** -> Submenu **Kartu Stok per Produk** -> Memilih produk dan rentang tanggal yang diinginkan -> Sistem menyusun rekonsiliasi saldo berjalan:
$$\text{Saldo Akhir} = \text{Stok Awal Periode} + \sum \text{Masuk} - \sum \text{Keluar} \pm \sum \text{Penyesuaian}$$
Dapat langsung diunduh ke format PDF maupun Excel.

---

## Akun Demo

Database seeder menyediakan 3 akun awal untuk kebutuhan demonstrasi dan pengujian:

| Username | Password | Role | Peruntukan Uji Coba |
|---|---|---|---|
| `admin` | `admin123` | **ADMIN** | Pengelolaan penuh sistem, master data, user, transaksi, dan laporan |
| `petugas` | `petugas123` | **PETUGAS** | Operasional harian: input barang masuk, barang keluar, dan stock opname |
| `owner` | `owner123` | **PEMILIK** | Pengawasan eksekutif: pantau dashboard, nilai persediaan aset, dan ekspor laporan |

> **Perhatian**: Untuk implementasi di lingkungan produksi, segera ubah seluruh password akun default di atas melalui menu Manajemen Pengguna.

---

## Panduan Instalasi Lokal

### Prasyarat Sistem
- **PHP**: Versi 8.3 atau 8.4 dengan ekstensi: `pdo_mysql`, `mbstring`, `openssl`, `curl`, `fileinfo`, `gd`, `intl`, `zip`.
- **Database**: MariaDB 10.4+ (XAMPP/Laragon) atau MySQL 8.0+.
- **Node.js**: Versi 18+ atau 20+ & npm.
- **Composer**: Versi 2.x+.

### Langkah-Langkah Instalasi

1. **Clone atau Buka Direktori Proyek**:
   ```bash
   cd monitoring-stok-telur-asin
   ```

2. **Pasang Dependensi PHP (Composer)**:
   ```bash
   composer install
   ```

3. **Pasang Dependensi Frontend (NPM)**:
   ```bash
   npm install
   ```

4. **Konfigurasi Berkas Lingkungan (.env)**:
   Salin berkas contoh konfigurasi jika belum tersedia:
   ```bash
   cp .env.example .env
   ```
   Pastikan pengaturan database di `.env` sudah sesuai dengan MariaDB/MySQL lokal Anda:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=sim_telur_asin
   DB_USERNAME=root
   DB_PASSWORD=

   APP_TIMEZONE=Asia/Jakarta
   APP_LOCALE=id

   # Opsional: Jika ingin menggunakan LLM Gemini langsung untuk Chatbot
   # GEMINI_API_KEY=AIzaSy...
   ```

5. **Buat Database & Jalankan Kunci Aplikasi**:
   Buat database baru di MySQL/MariaDB:
   ```sql
   CREATE DATABASE sim_telur_asin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
   Lalu generate kunci aplikasi:
   ```bash
   php artisan key:generate
   ```

6. **Jalankan Migrasi Database dan Seeder**:
   ```bash
   php artisan migrate --seed
   ```

7. **Kompilasi Aset Frontend**:
   Untuk mode pengembangan (*hot-reload*):
   ```bash
   npm run dev
   ```
   Atau untuk kompilasi berkas siap pakai:
   ```bash
   npm run build
   ```

8. **Jalankan Server Lokal**:
   ```bash
   php artisan serve
   ```
   Akses aplikasi melalui peramban web di alamat: `http://127.0.0.1:8000`.

---

## Pengujian Otomatis (Testing)

Aplikasi dilengkapi dengan rangkaian pengujian fitur (*Feature Tests*) lengkap yang mencakup seluruh skenario validasi, batasan peran, integritas transaksi, pencegahan manipulasi data, dan fungsionalitas ekspor:

```bash
php artisan test
```

### Ringkasan Cakupan Test (70 Tests - 100% Lulus):
- `RolePermissionTest`: Pembatasan hak akses Admin, Petugas, dan Pemilik; proteksi akun admin dari tindakan hapus/nonaktif diri sendiri.
- `MasterDataTest`: Validasi keunikan SKU, harga tidak negatif, pencegahan penghapusan master data yang berelasi, serta mekanisme *soft delete*.
- `StockMovementTest`: Penambahan stok saat barang masuk, pengurangan stok saat barang keluar, penolakan transaksi jika stok tidak mencukupi, penghitungan selisih stock opname, penolakan input tanggal masa depan.
- `DashboardTest`: Otorisasi tampilan nilai persediaan aset (khusus Admin & Pemilik), verifikasi banner stok menipis, banner kedaluwarsa, pemisahan agregasi masuk vs keluar hari ini, serta View Composer lonceng header.
- `ReportTest`: Filter laporan per mutasi/produk/petugas, verifikasi ekspor PDF, ekspor Excel, ekspor CSV dengan *escaping* tanda kutip, dan rekonsiliasi kartu stok.
- `UserManagementTest`: Pembuatan user baru, reset password oleh admin, serta pencegahan menonaktifkan akun admin terakhir.
- `ChatbotTest`: Akses terotentikasi, eksekusi tool calling baca data riil, logging percakapan ke `chat_logs`, *rate limiting*, dan pengujian bahwa chatbot tidak pernah memodifikasi stok.

---

## Panduan Deploy ke Server Produksi (cPanel & VPS)

### 1. Rekomendasi Pengaturan Produksi pada Berkas `.env`
Saat mengunggah ke hosting atau VPS, pastikan pengaturan keamanan berikut diterapkan:

```env
APP_NAME="SIM-STOK Telur Asin"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://domain-anda.com

LOG_CHANNEL=daily
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nama_database_hosting
DB_USERNAME=nama_user_hosting
DB_PASSWORD=password_database_kuat

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

QUEUE_CONNECTION=database
CACHE_STORE=database
```

---

### 2. Panduan Deploy ke cPanel (Shared Hosting)

1. **Kompilasi Aset di Komputer Lokal**:
   Sebelum mengunggah berkas, jalankan kompilasi produksi di lokal:
   ```bash
   npm run build
   ```
   Folder `public/build` akan berisi aset terkompresi (CSS dan JS).

2. **Struktur Folder di cPanel**:
   Demi keamanan maksimal, **jangan** letakkan seluruh proyek langsung di dalam `public_html`. Gunakan pemisahan struktur:
   - Buat folder di luar `public_html`, misalnya: `/home/username/sim_stok/`.
   - Unggah seluruh folder proyek Laravel ke `/home/username/sim_stok/` (kecuali folder `public`).
   - Pindahkan seluruh isi folder `public/` milik Laravel ke dalam folder `/home/username/public_html/`.

3. **Penyesuaian Berkas `index.php` pada `public_html`**:
   Buka file `/home/username/public_html/index.php`, sesuaikan path autoload dan bootstrap:
   ```php
   require __DIR__.'/../sim_stok/vendor/autoload.php';
   $app = require_once __DIR__.'/../sim_stok/bootstrap/app.php';
   ```

4. **Izin Akses Folder (*File Permissions*)**:
   Pastikan folder penyimpanan memiliki hak akses tulis:
   - Folder `storage` dan `bootstrap/cache` diatur ke izin **775** atau **755**.

5. **Migrasi Database di cPanel**:
   Buka menu **Terminal** di cPanel, lalu jalankan:
   ```bash
   cd ~/sim_stok
   php artisan migrate --force --seed
   ```

6. **Optimasi Cache Produksi**:
   Jalankan perintah cache di Terminal cPanel untuk kecepatan maksimal:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

---

### 3. Panduan Deploy ke VPS Linux (Ubuntu / Debian + Nginx)

1. **Persiapan Server**:
   Pasang paket yang diperlukan (Nginx, PHP 8.3/8.4-FPM, MariaDB, Composer, Git):
   ```bash
   sudo apt update
   sudo apt install -y nginx mariadb-server php8.4-fpm php8.4-mysql php8.4-mbstring \
       php8.4-xml php8.4-curl php8.4-gd php8.4-zip php8.4-intl git unzip
   ```

2. **Clone Repositori**:
   ```bash
   cd /var/www
   sudo git clone https://github.com/username/monitoring-stok-telur-asin.git sim-stok
   cd sim-stok
   ```

3. **Instalasi Dependensi & Konfigurasi Lingkungan**:
   ```bash
   composer install --no-dev --optimize-autoloader
   npm install && npm run build
   cp .env.example .env
   # Edit .env dan sesuaikan kredensial database & APP_URL
   nano .env
   php artisan key:generate
   ```

4. **Pengaturan Kepemilikan dan Izin Folder**:
   ```bash
   sudo chown -R www-data:www-data /var/www/sim-stok
   sudo chmod -R 775 /var/www/sim-stok/storage
   sudo chmod -R 775 /var/www/sim-stok/bootstrap/cache
   ```

5. **Migrasi Database & Symlink Storage**:
   ```bash
   php artisan migrate --force --seed
   php artisan storage:link
   ```

6. **Aktifkan Optimasi Cache**:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```

7. **Konfigurasi Virtual Host Nginx**:
   Buat konfigurasi situs `/etc/nginx/sites-available/sim-stok`:
   ```nginx
   server {
       listen 80;
       server_name domain-anda.com;
       root /var/www/sim-stok/public;

       add_header X-Frame-Options "SAMEORIGIN";
       add_header X-Content-Type-Options "nosniff";

       index index.php;
       charset utf-8;

       location / {
           try_files $uri $uri/ /index.php?$query_string;
       }

       location = /favicon.ico { access_log off; log_not_found off; }
       location = /robots.txt  { access_log off; log_not_found off; }

       error_page 404 /index.php;

       location ~ \.php$ {
           fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
           fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
           include fastcgi_params;
           fastcgi_hide_header X-Powered-By;
       }

       location ~ /\.(?!well-known).* {
           deny all;
       }
   }
   ```
   Aktifkan situs dan muat ulang Nginx:
   ```bash
   sudo ln -s /etc/nginx/sites-available/sim-stok /etc/nginx/sites-enabled/
   sudo nginx -t
   sudo systemctl restart nginx
   ```

8. **Pasang Sertifikat SSL Gratis (Let's Encrypt)**:
   ```bash
   sudo apt install -y certbot python3-certbot-nginx
   sudo certbot --nginx -d domain-anda.com
   ```

---

## Struktur Direktori Proyek

```text
├── app/
│   ├── Enums/                 # Role (ADMIN, PETUGAS, OWNER) & MovementType
│   ├── Http/
│   │   ├── Controllers/       # Dashboard, Product, Category, Unit, Supplier, Stock, Report, User, Chatbot
│   │   ├── Middleware/        # CheckRole middleware
│   │   └── Requests/          # Form Requests dengan validasi ketat
│   ├── Models/                # Product, StockMovement, Category, Unit, Supplier, User, ChatLog
│   ├── Policies/              # ProductPolicy
│   ├── Services/              # StockService (Core transaksi aman) & ChatbotService (AI tool-calling)
│   └── View/Composers/        # HeaderComposer (penghitung stok menipis pada navbar)
├── config/                    # Konfigurasi aplikasi, permission, dompdf, excel
├── database/
│   ├── factories/             # Factory pengujian
│   ├── migrations/            # Skema tabel database (termasuk soft deletes & chat_logs)
│   └── seeders/               # RoleSeeder, UserSeeder, CategorySeeder, UnitSeeder, ProductSeeder
├── resources/
│   ├── css/                   # app.css (Tailwind CSS v4)
│   ├── js/                    # app.js (Alpine.js & Chart.js)
│   └── views/
│       ├── auth/              # Halaman login modern bernuansa amber
│       ├── categories/        # Tampilan CRUD kategori
│       ├── components/        # Blade components (nav-link, chatbot-widget, stat-card)
│       ├── layouts/           # Layout utama aplikasi dengan sidebar & header responsif
│       ├── products/          # Tampilan katalog produk, filter, dan pagination
│       ├── reports/           # Laporan mutasi, kartu stok, template PDF & Excel
│       ├── stock/             # Form Barang Masuk, Barang Keluar, dan Stock Opname
│       ├── suppliers/         # Tampilan CRUD supplier
│       ├── units/             # Tampilan CRUD satuan
│       └── users/             # Tampilan manajemen user dan form reset password
├── routes/
│   ├── auth.php               # Rute autentikasi
│   ├── console.php            # Perintah artisan konsol
│   └── web.php                # Rute web terproteksi middleware auth & role
└── tests/
    └── Feature/               # 7 Feature Test Suites (70 pengujian otomatis)
```

---

## Lisensi & Hak Cipta

Proyek ini dikembangkan untuk keperluan akademik tugas akhir / skripsi sistem informasi persediaan barang dagang telur asin. Bebas digunakan dan dikembangkan lebih lanjut untuk kepentingan edukasi dan riset.
