<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modifikasi ENUM type jika memakai MariaDB / MySQL
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_logs MODIFY COLUMN type ENUM('MASUK', 'KELUAR', 'PENYESUAIAN') NOT NULL");
        }

        // 2. Tambahkan kolom stok_sebelum dan stok_sesudah
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->integer('stok_sebelum')->default(0)->after('amount');
            $table->integer('stok_sesudah')->default(0)->after('stok_sebelum');
        });
    }

    public function down(): void
    {
        Schema::table('stock_logs', function (Blueprint $table) {
            $table->dropColumn(['stok_sebelum', 'stok_sesudah']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE stock_logs MODIFY COLUMN type ENUM('MASUK', 'KELUAR') NOT NULL");
        }
    }
};
