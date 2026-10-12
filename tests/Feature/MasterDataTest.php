<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $petugas;
    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->petugas = User::factory()->create();
        $this->owner = User::factory()->owner()->create();
    }

    public function test_hanya_admin_yang_dapat_mengakses_crud_kategori(): void
    {
        $this->actingAs($this->admin)->get(route('categories.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('categories.create'))->assertOk();

        $this->actingAs($this->petugas)->get(route('categories.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('categories.index'))->assertForbidden();
    }

    public function test_admin_dapat_membuat_mengubah_dan_menghapus_kategori(): void
    {
        $response = $this->actingAs($this->admin)->post(route('categories.store'), [
            'name' => 'Kategori Istimewa',
        ]);
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Kategori Istimewa']);

        $category = Category::where('name', 'Kategori Istimewa')->first();

        $response = $this->actingAs($this->admin)->put(route('categories.update', $category), [
            'name' => 'Kategori Diubah',
        ]);
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseHas('categories', ['name' => 'Kategori Diubah']);

        $response = $this->actingAs($this->admin)->delete(route('categories.destroy', $category));
        $response->assertRedirect(route('categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_kategori_tidak_dapat_dihapus_jika_memiliki_produk(): void
    {
        $category = Category::factory()->create(['name' => 'Kategori Berisi']);
        Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)->delete(route('categories.destroy', $category));
        $response->assertSessionHasErrors('category');
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_hanya_admin_yang_dapat_mengakses_crud_satuan(): void
    {
        $this->actingAs($this->admin)->get(route('units.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('units.create'))->assertOk();

        $this->actingAs($this->petugas)->get(route('units.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('units.index'))->assertForbidden();
    }

    public function test_admin_dapat_membuat_mengubah_dan_menghapus_satuan(): void
    {
        $response = $this->actingAs($this->admin)->post(route('units.store'), [
            'name' => 'kardus',
        ]);
        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['name' => 'kardus']);

        $unit = Unit::where('name', 'kardus')->first();

        $response = $this->actingAs($this->admin)->put(route('units.update', $unit), [
            'name' => 'box',
        ]);
        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseHas('units', ['name' => 'box']);

        $response = $this->actingAs($this->admin)->delete(route('units.destroy', $unit));
        $response->assertRedirect(route('units.index'));
        $this->assertDatabaseMissing('units', ['id' => $unit->id]);
    }

    public function test_satuan_tidak_dapat_dihapus_jika_memiliki_produk(): void
    {
        $unit = Unit::firstOrCreate(['name' => 'butir']);
        Product::factory()->create(['unit_id' => $unit->id]);

        $response = $this->actingAs($this->admin)->delete(route('units.destroy', $unit));
        $response->assertSessionHasErrors('unit');
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_hanya_admin_yang_dapat_mengakses_crud_supplier(): void
    {
        $this->actingAs($this->admin)->get(route('suppliers.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('suppliers.create'))->assertOk();

        $this->actingAs($this->petugas)->get(route('suppliers.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('suppliers.index'))->assertForbidden();
    }

    public function test_admin_dapat_membuat_mengubah_dan_menghapus_supplier(): void
    {
        $response = $this->actingAs($this->admin)->post(route('suppliers.store'), [
            'name' => 'Peternak Bebek Pak Haji',
            'phone' => '081234567890',
            'address' => 'Desa Limbangan, Brebes',
        ]);
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Peternak Bebek Pak Haji',
            'phone' => '081234567890',
        ]);

        $supplier = Supplier::where('name', 'Peternak Bebek Pak Haji')->first();

        $response = $this->actingAs($this->admin)->put(route('suppliers.update', $supplier), [
            'name' => 'Peternak Bebek Barokah',
            'phone' => '089876543210',
            'address' => 'Ketanggungan, Brebes',
        ]);
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseHas('suppliers', [
            'name' => 'Peternak Bebek Barokah',
            'phone' => '089876543210',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('suppliers.destroy', $supplier));
        $response->assertRedirect(route('suppliers.index'));
        $this->assertDatabaseMissing('suppliers', ['id' => $supplier->id]);
    }

    public function test_validasi_produk_sku_unik_serta_harga_dan_stok_minimum_tidak_negatif(): void
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();
        $existingProduct = Product::factory()->create(['sku' => 'TLR-EXISTING-01']);

        // SKU duplikat harus ditolak
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'sku' => 'TLR-EXISTING-01',
            'name' => 'Produk Lain',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => 4000,
            'min_stock' => 10,
        ]);
        $response->assertSessionHasErrors('sku');

        // Harga negatif harus ditolak
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'sku' => 'TLR-NEW-01',
            'name' => 'Produk Baru',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => -1000,
            'min_stock' => 10,
        ]);
        $response->assertSessionHasErrors('price');

        // Stok minimum negatif harus ditolak
        $response = $this->actingAs($this->admin)->post(route('products.store'), [
            'sku' => 'TLR-NEW-02',
            'name' => 'Produk Baru 2',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => 4500,
            'min_stock' => -5,
        ]);
        $response->assertSessionHasErrors('min_stock');
    }

    public function test_produk_baru_selalu_memiliki_stok_awal_nol(): void
    {
        $category = Category::factory()->create();
        $unit = Unit::factory()->create();

        $this->actingAs($this->admin)->post(route('products.store'), [
            'sku' => 'TLR-STOK-01',
            'name' => 'Telur Baru',
            'category_id' => $category->id,
            'unit_id' => $unit->id,
            'price' => 5000,
            'purchase_price' => 4000,
            'min_stock' => 20,
            'stock' => 100, // input manual stock harus diabaikan/dipaksa 0
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'TLR-STOK-01',
            'stock' => 0,
        ]);
    }

    public function test_hapus_produk_menggunakan_soft_delete(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->admin)->delete(route('products.destroy', $product));
        $response->assertRedirect(route('products.index'));

        // Terhapus secara soft delete (deleted_at tidak null)
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_barang_masuk_bisa_memilih_supplier_dari_daftar(): void
    {
        $supplier = Supplier::factory()->create(['name' => 'Peternakan Jaya Brebes']);
        $product = Product::factory()->create(['stock' => 20]);

        $response = $this->actingAs($this->petugas)->post(route('stock.masuk.store'), [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'amount' => 30,
            'note' => 'Pasokan pagi hari',
        ]);

        $response->assertRedirect(route('dashboard'));

        $this->assertSame(50, $product->fresh()->stock);
        $this->assertDatabaseHas('stock_logs', [
            'product_id' => $product->id,
            'supplier_id' => $supplier->id,
            'source_or_target' => 'Peternakan Jaya Brebes',
            'amount' => 30,
        ]);
    }

    /** @return array<string, mixed> */
    private function productPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'sku' => $product->sku,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'unit_id' => $product->unit_id,
            'price' => $product->price,
            'purchase_price' => $product->purchase_price,
            'min_stock' => $product->min_stock,
        ], $overrides);
    }

    public function test_satuan_tidak_bisa_diubah_bila_produk_sudah_memiliki_mutasi(): void
    {
        $pack = Unit::factory()->create(['name' => 'lusin']);
        $product = Product::factory()->create();
        StockLog::factory()->for($product)->create();

        $this->actingAs($this->admin)
            ->put(route('products.update', $product), $this->productPayload($product, ['unit_id' => $pack->id]))
            ->assertSessionHasErrors('unit_id');

        $this->assertNotSame($pack->id, $product->fresh()->unit_id);

        // Field lain tetap bisa diubah selama satuan tidak diganti.
        $this->actingAs($this->admin)
            ->put(route('products.update', $product), $this->productPayload($product, ['name' => 'Nama Baru']))
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $product->fresh()->name);
    }

    public function test_satuan_masih_bisa_diubah_bila_produk_belum_pernah_dimutasi(): void
    {
        $pack = Unit::factory()->create(['name' => 'lusin']);
        $product = Product::factory()->create();

        $this->actingAs($this->admin)
            ->put(route('products.update', $product), $this->productPayload($product, ['unit_id' => $pack->id]))
            ->assertSessionHasNoErrors();

        $this->assertSame($pack->id, $product->fresh()->unit_id);
    }

    public function test_form_edit_mengunci_pilihan_satuan_bila_sudah_ada_mutasi(): void
    {
        $product = Product::factory()->create();
        StockLog::factory()->for($product)->create();

        $this->actingAs($this->admin)
            ->get(route('products.edit', $product))
            ->assertOk()
            ->assertSee('Terkunci: produk sudah memiliki riwayat mutasi.');
    }

    public function test_harga_beli_wajib_diisi_saat_menambah_produk(): void
    {
        $product = Product::factory()->make(['sku' => 'TLR-TANPA-MODAL']);

        foreach ([null, 0] as $hargaBeli) {
            $this->actingAs($this->admin)
                ->post(route('products.store'), $this->productPayload($product, ['purchase_price' => $hargaBeli]))
                ->assertSessionHasErrors('purchase_price');
        }

        $this->assertDatabaseMissing('products', ['sku' => 'TLR-TANPA-MODAL']);
    }

    public function test_produk_yang_sudah_dihapus_tidak_bisa_dimutasi(): void
    {
        $product = Product::factory()->create(['stock' => 50]);
        $product->delete();

        $payload = [
            'logged_date' => today()->toDateString(),
            'product_id' => $product->id,
            'amount' => 5,
            'source_or_target' => 'Pelanggan',
            'status' => 'Terjual',
        ];

        $this->actingAs($this->petugas)
            ->post(route('stock.keluar.store'), $payload)
            ->assertSessionHasErrors(['product_id' => 'Varian telur tidak ditemukan atau sudah dihapus.']);

        $this->actingAs($this->petugas)
            ->post(route('stock.opname.store'), [
                'logged_date' => today()->toDateString(),
                'product_id' => $product->id,
                'stok_fisik' => 10,
                'alasan' => 'Hitung ulang',
            ])
            ->assertSessionHasErrors('product_id');

        $this->assertSame(0, StockLog::count());
    }
}
