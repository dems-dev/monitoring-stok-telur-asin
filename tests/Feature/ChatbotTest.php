<?php

namespace Tests\Feature;

use App\Enums\LogType;
use App\Models\ChatLog;
use App\Models\Product;
use App\Models\StockLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ChatbotTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_tidak_bisa_mengakses_chatbot(): void
    {
        $this->postJson('/chatbot/message', ['message' => 'Halo'])
            ->assertUnauthorized();

        $this->getJson('/chatbot/history')
            ->assertUnauthorized();
    }

    public function test_user_bisa_mengirim_pesan_dan_menerima_tanggapan(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Halo selamat pagi',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['reply']);
        $this->assertNotEmpty($response->json('reply'));
    }

    public function test_chatbot_dapat_mengecek_stok_produk_tertentu(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Telur Asin Original Rebus',
            'sku' => 'TLR-ORIG-01',
            'stock' => 125,
            'price' => 3500,
        ]);

        $response = $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Cek stok telur asin original',
        ]);

        $response->assertOk();
        $response->assertJson([
            'tool_called' => 'cek_stok',
        ]);
        $this->assertStringContainsString('125', $response->json('reply'));
        $this->assertStringContainsString('TLR-ORIG-01', $response->json('reply'));
    }

    public function test_chatbot_dapat_menampilkan_daftar_stok_menipis(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create([
            'name' => 'Telur Asin Bumbu Masir',
            'stock' => 5,
            'min_stock' => 10,
        ]);

        $response = $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Apakah ada produk yang stoknya menipis?',
        ]);

        $response->assertOk();
        $response->assertJson([
            'tool_called' => 'daftar_stok_menipis',
        ]);
        $this->assertStringContainsString('Telur Asin Bumbu Masir', $response->json('reply'));
        $this->assertStringContainsString('5', $response->json('reply'));
    }

    public function test_chatbot_dapat_menampilkan_produk_terlaris(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Telur Asin Panggang']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::KELUAR,
            'amount' => 88,
            'logged_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Tampilkan produk terlaris bulan ini',
        ]);

        $response->assertOk();
        $response->assertJson([
            'tool_called' => 'produk_terlaris',
        ]);
        $this->assertStringContainsString('Telur Asin Panggang', $response->json('reply'));
        $this->assertStringContainsString('88', $response->json('reply'));
    }

    public function test_chatbot_dapat_menampilkan_riwayat_barang_masuk(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['name' => 'Telur Asin Asap']);

        StockLog::factory()->for($product)->create([
            'type' => LogType::MASUK,
            'amount' => 150,
            'source_or_target' => 'Dapur Pengasinan Brebes',
            'logged_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Bagaimana riwayat pasokan barang masuk seminggu terakhir?',
        ]);

        $response->assertOk();
        $response->assertJson([
            'tool_called' => 'riwayat_barang_masuk',
        ]);
        $this->assertStringContainsString('Telur Asin Asap', $response->json('reply'));
        $this->assertStringContainsString('150', $response->json('reply'));
    }

    public function test_chatbot_mencatat_log_percakapan_ke_database(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Cek stok telur menipis',
        ]);

        $this->assertDatabaseHas('chat_logs', [
            'user_id' => $user->id,
            'message' => 'Cek stok telur menipis',
            'tool_called' => 'daftar_stok_menipis',
        ]);
    }

    public function test_chatbot_tidak_mengubah_data_stok_hanya_baca(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 100]);

        $this->actingAs($user)->postJson('/chatbot/message', [
            'message' => 'Kurangi stok telur 10 butir',
        ]);

        // Stok produk tidak boleh berubah dan tidak boleh ada StockLog baru dibuat
        $this->assertSame(100, $product->fresh()->stock);
        $this->assertSame(0, StockLog::count());
    }

    public function test_chatbot_menerapkan_rate_limit(): void
    {
        $user = User::factory()->create();
        RateLimiter::clear('chatbot:' . $user->id);

        for ($i = 0; $i < 20; $i++) {
            $this->actingAs($user)->postJson('/chatbot/message', ['message' => 'Tes']);
        }

        // Permintaan ke-21 harus ditolak dengan kode 429
        $response = $this->actingAs($user)->postJson('/chatbot/message', ['message' => 'Tes ke 21']);
        $response->assertStatus(429);
        $response->assertJsonStructure(['error', 'reply']);
    }

    public function test_user_bisa_melihat_dan_menghapus_riwayat_chat(): void
    {
        $user = User::factory()->create();

        ChatLog::create([
            'user_id' => $user->id,
            'message' => 'Pesan 1',
            'response' => 'Jawaban 1',
        ]);

        // Cek riwayat
        $this->actingAs($user)->getJson('/chatbot/history')
            ->assertOk()
            ->assertJsonCount(1, 'history');

        // Hapus riwayat
        $this->actingAs($user)->deleteJson('/chatbot/history')
            ->assertOk();

        $this->assertDatabaseMissing('chat_logs', ['user_id' => $user->id]);
    }
}
