<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['MASUK', 'KELUAR']);
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            // Petugas pencatat; nullable agar penghapusan user tidak menghapus riwayat.
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('source_or_target');
            $table->string('status', 50);
            $table->text('note')->nullable();
            // Tanggal yang diisi petugas, terpisah dari created_at (waktu entri sebenarnya).
            $table->date('logged_date');
            $table->timestamps();

            $table->index(['logged_date', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_logs');
    }
};
