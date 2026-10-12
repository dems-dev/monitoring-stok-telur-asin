<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kolom category_id dan unit_id (nullable dulu untuk migrasi data)
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('category_id')->nullable()->after('name');
            $table->unsignedBigInteger('unit_id')->nullable()->after('category_id');
        });

        // 2. Buat unit bawaan jika belum ada
        $now = now();
        $butirId = DB::table('units')->where('name', 'butir')->value('id');
        if (! $butirId) {
            $butirId = DB::table('units')->insertGetId([
                'name' => 'butir',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $packId = DB::table('units')->where('name', 'pack')->value('id');
        if (! $packId) {
            $packId = DB::table('units')->insertGetId([
                'name' => 'pack',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // 3. Konversi kategori lama dari kolom 'category' ke tabel 'categories'
        $defaultCategories = ['Original', 'Bakar', 'Asap', 'Bumbu/Rasa', 'Kemasan', 'Mentah'];
        foreach ($defaultCategories as $catName) {
            if (! DB::table('categories')->where('name', $catName)->exists()) {
                DB::table('categories')->insert([
                    'name' => $catName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        $existingCategories = DB::table('products')
            ->whereNotNull('category')
            ->pluck('category')
            ->unique();

        foreach ($existingCategories as $catName) {
            $catId = DB::table('categories')->where('name', $catName)->value('id');
            if (! $catId) {
                $catId = DB::table('categories')->insertGetId([
                    'name' => $catName,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }

            DB::table('products')
                ->where('category', $catName)
                ->update(['category_id' => $catId]);
        }

        // 4. Set unit_id untuk produk yang sudah ada (Pack jika kemasan/ada kata pack, selain itu butir)
        DB::table('products')->whereNull('unit_id')->get()->each(function ($p) use ($butirId, $packId) {
            $isPack = str_contains(strtolower($p->name), 'pack') || strtolower($p->category ?? '') === 'kemasan';
            DB::table('products')->where('id', $p->id)->update([
                'unit_id' => $isPack ? $packId : $butirId,
            ]);
        });

        // Fallback jika ada produk tanpa category_id
        $firstCatId = DB::table('categories')->value('id');
        if ($firstCatId) {
            DB::table('products')->whereNull('category_id')->update(['category_id' => $firstCatId]);
        }

        // 5. Tambah foreign key constraints dan hapus kolom lama 'category'
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('categories')->restrictOnDelete();
            $table->foreign('unit_id')->references('id')->on('units')->restrictOnDelete();
            $table->dropColumn('category');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('category', 50)->nullable()->after('name');
        });

        $products = DB::table('products')->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.id', 'categories.name as cat_name')
            ->get();

        foreach ($products as $p) {
            DB::table('products')->where('id', $p->id)->update(['category' => $p->cat_name]);
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['unit_id']);
            $table->dropColumn(['category_id', 'unit_id']);
        });
    }
};
