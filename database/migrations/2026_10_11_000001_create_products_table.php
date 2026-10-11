<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 50)->unique();
            $table->string('name');
            $table->string('category', 50)->index();
            // Harga satuan dalam rupiah penuh, tanpa pecahan sen.
            $table->unsignedInteger('price')->default(0);
            // Stok disimpan signed agar koreksi data yang keliru tidak gagal diam-diam.
            $table->integer('stock')->default(0);
            $table->unsignedInteger('min_stock')->default(0);
            $table->timestamps();
            // Produk dihapus lunak supaya riwayat mutasi di stock_logs tetap utuh.
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
