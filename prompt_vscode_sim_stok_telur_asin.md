# Prompt VS Code — Pembangunan Ulang SIM-STOK Telur Asin

Cara pakai:
1. Buat folder proyek kosong, buka di VS Code.
2. Tempel **PROMPT UTAMA** ke AI agent (Copilot Agent Mode / Claude Code / Cursor) sekali di awal.
3. Lanjutkan dengan **PROMPT FASE 1, 2, 3, dst.** satu per satu. Jangan langsung semua.
4. Setelah setiap fase: jalankan aplikasi, cek hasilnya, commit ke Git, baru lanjut fase berikutnya.
5. Sertakan file HTML prototipe lama (`sistem_monitoring_persediaan_telur_asin.html`) di folder proyek sebagai referensi tampilan.

---

## PROMPT UTAMA (konteks proyek)

```
Kamu adalah senior full-stack developer Laravel. Bantu saya membangun ulang aplikasi
"SIM-STOK Telur Asin" — Sistem Monitoring Persediaan Barang Berbasis Web untuk toko
telur asin. Ini proyek skripsi, jadi kode harus rapi, aman, mudah dijelaskan, dan
diberi komentar secukupnya dalam Bahasa Indonesia.

File `sistem_monitoring_persediaan_telur_asin.html` di folder ini adalah prototipe lama
(hanya front-end + localStorage). Gunakan sebagai REFERENSI TAMPILAN dan alur saja.
Jangan meniru cara penyimpanan data maupun logika keamanannya.

## Tech stack (wajib)
- Backend   : Laravel versi stabil terbaru (PHP 8.3+)
- Database  : MySQL 8 (lewat Laravel migration & seeder, Eloquent ORM)
- Auth      : Laravel Breeze (Blade stack) — login, logout, hash password bcrypt
- Role      : spatie/laravel-permission — role: admin, petugas, pemilik
- Frontend  : Blade + Tailwind CSS (via Vite, BUKAN CDN) + Alpine.js
- Grafik    : Chart.js (via npm)
- Ikon      : Font Awesome atau Heroicons (via npm)
- Laporan   : barryvdh/laravel-dompdf (PDF) dan maatwebsite/excel (Excel)
- Timezone  : Asia/Jakarta, locale id (atur di config/app.php)
- Testing   : Pest atau PHPUnit (Feature test)

## Aturan penting
1. Stok produk HANYA boleh berubah lewat transaksi (barang masuk, barang keluar,
   penyesuaian stok/opname). Admin TIDAK boleh mengedit kolom stok secara langsung.
2. Setiap perubahan stok dibungkus DB::transaction() dan memakai lockForUpdate()
   agar tidak terjadi race condition.
3. Setiap transaksi mencatat user_id pembuatnya (audit trail).
4. Semua pengecekan hak akses dilakukan di server (middleware role / Policy / Gate),
   bukan hanya menyembunyikan tombol.
5. Semua input divalidasi dengan Form Request: angka >= 0 / >= 1, SKU unik,
   tanggal transaksi tidak boleh di masa depan.
6. Semua output di Blade memakai {{ }} (auto-escape). Dilarang {!! !!} untuk data user.
7. Gunakan satuan yang jelas per produk (butir / pack) — jangan menjumlahkan satuan
   berbeda menjadi satu angka total.
8. Tampilan mengikuti prototipe: warna utama amber, font Plus Jakarta Sans,
   sidebar kiri, kartu dashboard, responsif di HP.

## Hak akses
- admin   : semua fitur, termasuk kelola user, master data, kategori, supplier.
- petugas : barang masuk, barang keluar, stock opname, lihat stok & laporan.
- pemilik : hanya lihat (dashboard, stok, laporan, ekspor). Tidak bisa input/ubah.

Jangan langsung membuat semuanya. Saya akan memberi instruksi per fase.
Untuk sekarang: jelaskan singkat struktur folder yang akan dibuat dan daftar
perintah instalasi (composer & npm) yang perlu saya jalankan. Tunggu konfirmasi saya.
```

---

## PROMPT FASE 1 — Setup proyek & autentikasi

```
Fase 1: Setup proyek dan autentikasi.
1. Inisialisasi proyek Laravel, konfigurasi .env untuk MySQL (database: sim_stok_telur),
   timezone Asia/Jakarta, locale id.
2. Install Breeze (Blade), Tailwind via Vite, Alpine.js, spatie/laravel-permission.
3. Buat role admin, petugas, pemilik lewat seeder.
4. Buat UserSeeder dengan 3 akun contoh (password di-hash). Kredensial demo hanya
   ditulis di README.md, TIDAK ditampilkan di halaman login.
5. Ubah tampilan halaman login mengikuti prototipe (kartu putih, logo telur, warna amber).
6. Lupa password: gunakan fitur reset password bawaan Breeze (token via email,
   pakai MAIL_MAILER=log untuk pengembangan). Jangan buat reset password tanpa verifikasi.
7. Tambahkan rate limiting pada login (bawaan Breeze, pastikan aktif).
8. Buat layout utama (sidebar + header) dengan menu yang tampil sesuai role
   menggunakan @role / @can.
Berikan semua perintah dan file yang dibuat/diubah.
```

---

## PROMPT FASE 2 — Database & master data

```
Fase 2: Database dan master data.
Buat migration, model (dengan relasi), factory, dan seeder untuk tabel:
- categories (id, nama)
- units (id, nama) — contoh: butir, pack
- suppliers (id, nama, telepon, alamat)
- products (id, sku UNIQUE, nama, category_id, unit_id, harga_beli, harga_jual,
  stok DEFAULT 0, stok_minimum, tanggal_kedaluwarsa_terdekat NULLABLE, timestamps,
  softDeletes)
- stock_movements (id, product_id, user_id, supplier_id NULLABLE,
  tipe ENUM('masuk','keluar','penyesuaian'),
  kategori_keluar ENUM('terjual','distribusi','rusak','kedaluwarsa') NULLABLE,
  jumlah, stok_sebelum, stok_sesudah, tujuan_atau_sumber, catatan,
  tanggal, timestamps)

Lalu buat CRUD (Controller resource + Form Request + Policy + Blade view) untuk:
categories, units, suppliers, products — hanya admin yang boleh create/update/delete.
Pada form produk: TIDAK ADA input stok. Stok awal diisi lewat transaksi barang masuk.
Hapus produk memakai soft delete agar riwayat transaksi tetap utuh.
Tabel produk punya pencarian (SKU/nama), filter kategori, pagination, dan badge status
(Aman / Menipis / Habis).
Isi seeder dengan 6 produk telur asin seperti di prototipe.
```

---

## PROMPT FASE 3 — Transaksi stok

```
Fase 3: Transaksi stok.
Buat app/Services/StockService.php dengan method:
- barangMasuk(Product, jumlah, data)
- barangKeluar(Product, jumlah, data)   -> tolak jika stok tidak cukup
- penyesuaian(Product, stokFisik, alasan) -> untuk stock opname
Setiap method: DB::transaction + lockForUpdate, simpan stok_sebelum & stok_sesudah,
catat user_id = auth()->id().

Buat halaman (Controller + Form Request + Blade) untuk:
1. Barang Masuk  (tanggal, produk, jumlah, supplier, catatan)
2. Barang Keluar (tanggal, produk, jumlah, kategori keluar, tujuan, catatan)
3. Stock Opname  (pilih produk, isi stok fisik, sistem menampilkan selisih, alasan wajib)
Hanya admin & petugas yang boleh mengakses (middleware role).
Validasi: jumlah >= 1, tanggal <= hari ini.
Setelah simpan, tampilkan flash message sukses (bukan alert JavaScript).
Tulis Feature test untuk: stok bertambah/berkurang benar, barang keluar melebihi stok
ditolak, pemilik ditolak (403) saat mengakses halaman transaksi.
```

---

## PROMPT FASE 4 — Dashboard & monitoring

```
Fase 4: Dashboard monitoring.
Buat DashboardController dan view dengan:
- Kartu: jumlah produk, nilai persediaan (stok x harga_beli, hanya untuk admin & pemilik),
  barang masuk hari ini, barang keluar hari ini (DIPISAH, bukan dijumlah),
  jumlah produk stok menipis.
- Banner peringatan daftar produk yang stok <= stok_minimum.
- Peringatan produk yang mendekati kedaluwarsa (<= 3 hari).
- Grafik Chart.js:
  a) Stok per produk (bar) dengan batas minimum sebagai garis (line), bukan bar.
  b) Tren barang masuk vs keluar 30 hari terakhir (line).
  c) 5 produk terlaris bulan ini (horizontal bar).
- Tabel 5 transaksi terakhir, diurutkan berdasarkan tanggal lalu created_at.
- Badge jumlah stok menipis di ikon lonceng header (pakai View Composer).
Semua perhitungan dilakukan di server (query Eloquent), bukan di JavaScript.
```

---

## PROMPT FASE 5 — Laporan & manajemen user

```
Fase 5: Laporan dan manajemen user.
1. Halaman Riwayat & Laporan: filter tipe, produk, rentang tanggal, user; pagination.
2. Ekspor PDF (dompdf) dengan kop laporan, periode, nama pencetak, dan tanggal cetak.
3. Ekspor Excel (maatwebsite/excel).
4. Laporan kartu stok per produk (stok awal, masuk, keluar, saldo per tanggal).
5. Manajemen user (admin saja): tambah, edit, nonaktifkan user, ubah role,
   reset password (admin mengetik password baru, tidak ditampilkan ulang lewat alert).
   Admin tidak boleh menghapus/menonaktifkan akunnya sendiri.
```

---

## PROMPT FASE 6 (opsional) — AI chatbot persediaan

```
Fase 6: Integrasi AI chatbot (hanya-baca).
Buat widget chat di pojok kanan bawah (Alpine.js) untuk user yang login.
Backend: ChatbotController yang memanggil API LLM (API key di .env, misalnya
LLM_API_KEY) dengan pendekatan tool/function calling.
Sediakan tools yang dieksekusi di server:
- cek_stok(nama_produk)
- daftar_stok_menipis()
- produk_terlaris(periode)
- riwayat_barang_masuk(nama_produk, jumlah_hari)
LLM hanya menyusun jawaban dari hasil tools; angka berasal dari database.
Chatbot TIDAK BOLEH mengubah data apa pun. Simpan log percakapan di tabel chat_logs.
Tambahkan rate limit per user dan pesan error ramah jika API gagal.
```

---

## PROMPT FASE 7 — Finalisasi & deploy

```
Fase 7: Finalisasi.
1. Jalankan seluruh test dan perbaiki yang gagal.
2. Cek ulang: tidak ada {!! !!} untuk data user, semua route terlindungi middleware,
   semua form punya @csrf.
3. Buat README.md: deskripsi, fitur, tech stack, cara instalasi, akun demo, screenshot.
4. Siapkan panduan deploy ke hosting (shared hosting cPanel atau VPS): build aset
   `npm run build`, `php artisan migrate --force`, `config:cache`, `route:cache`,
   APP_DEBUG=false.
```

---

## Tips saat memakai prompt

- Jika AI membuat error, salin pesan error lengkap dan minta: "Perbaiki error ini tanpa mengubah fitur lain."
- Minta AI menjelaskan kode penting (StockService, Policy) — penjelasannya bisa dipakai untuk BAB IV.
- Commit Git setiap fase selesai, agar mudah kembali jika ada yang rusak.
