<?php

namespace App\Services;

use App\Models\ChatLog;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotService
{
    public function __construct(
        protected ChatbotToolService $tools
    ) {}

    /**
     * Memproses pesan chat dari user, mengeksekusi tool yang relevan,
     * menyusun respons, dan menyimpan riwayat ke chat_logs.
     *
     * @return array{response: string, tool_called: ?string, tool_result: ?array}
     */
    public function handle(User $user, string $message): array
    {
        $apiKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY') ?: env('LLM_API_KEY');

        $toolCalled = null;
        $toolArgs = null;
        $toolResult = null;
        $reply = null;

        if (!empty($apiKey)) {
            try {
                $llmResult = $this->callGeminiWithTools($apiKey, $message);
                $toolCalled = $llmResult['tool_called'];
                $toolArgs = $llmResult['tool_args'];
                $toolResult = $llmResult['tool_result'];
                $reply = $llmResult['response'];
            } catch (\Throwable $e) {
                Log::warning('Gemini API call failed, falling back to local resolver: ' . $e->getMessage());
            }
        }

        // Jika API key tidak ada atau panggilan API gagal, gunakan local fallback resolver
        if ($reply === null) {
            $fallback = $this->resolveLocally($message);
            $toolCalled = $fallback['tool_called'];
            $toolArgs = $fallback['tool_args'];
            $toolResult = $fallback['tool_result'];
            $reply = $fallback['response'];
        }

        // Simpan log percakapan ke database (audit trail)
        ChatLog::create([
            'user_id' => $user->id,
            'message' => $message,
            'response' => $reply,
            'tool_called' => $toolCalled,
            'tool_args' => $toolArgs,
            'tool_result' => $toolResult,
        ]);

        return [
            'response' => $reply,
            'tool_called' => $toolCalled,
            'tool_result' => $toolResult,
        ];
    }

    /**
     * Panggilan ke Gemini API dengan deklarasi tool / function calling.
     *
     * @return array{response: string, tool_called: ?string, tool_args: ?array, tool_result: ?array}
     */
    protected function callGeminiWithTools(string $apiKey, string $message): array
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

        $toolDeclarations = [
            [
                'function_declarations' => [
                    [
                        'name' => 'cek_stok',
                        'description' => 'Cek stok, batas minimum, harga, dan kondisi varian produk telur asin berdasarkan nama atau SKU produk.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'nama_produk' => [
                                    'type' => 'STRING',
                                    'description' => 'Nama varian atau kode SKU telur asin (contoh: Original, Panggang, Asap, Bumbu Masir, TLR-ORIG-01)',
                                ],
                            ],
                            'required' => ['nama_produk'],
                        ],
                    ],
                    [
                        'name' => 'daftar_stok_menipis',
                        'description' => 'Melihat daftar produk telur asin yang persediaannya menipis atau habis (stok <= batas minimum).',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => new \stdClass(),
                        ],
                    ],
                    [
                        'name' => 'produk_terlaris',
                        'description' => 'Melihat 5 produk terlaris berdasarkan mutasi pengeluaran/penjualan.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'periode' => [
                                    'type' => 'STRING',
                                    'description' => 'Pilihan rentang waktu: 7_hari, 30_hari, atau bulan_ini',
                                    'enum' => ['7_hari', '30_hari', 'bulan_ini'],
                                ],
                            ],
                        ],
                    ],
                    [
                        'name' => 'riwayat_barang_masuk',
                        'description' => 'Melihat riwayat transaksi pasokan barang masuk dalam beberapa hari terakhir.',
                        'parameters' => [
                            'type' => 'OBJECT',
                            'properties' => [
                                'nama_produk' => [
                                    'type' => 'STRING',
                                    'description' => 'Nama varian produk tertentu (opsional)',
                                ],
                                'jumlah_hari' => [
                                    'type' => 'INTEGER',
                                    'description' => 'Jumlah hari terakhir (default: 7)',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $systemInstruction = "Anda adalah Asisten Virtual SIM-STOK Telur Asin Brebes. Jawab pertanyaan pengguna secara ringkas, sopan, dan jelas dalam Bahasa Indonesia. "
            . "PENTING: Seluruh angka stok, harga, dan riwayat mutasi WAJIB diperoleh dari pemanggilan function tools yang tersedia. Jangan pernah mengarang angka atau data persediaan. "
            . "Jika pengguna hanya menyapa (halo/hai/pagi), perkenalkan diri Anda dan sebutkan bantuan yang bisa Anda berikan (cek stok produk, daftar stok menipis, produk terlaris, riwayat pasokan masuk).";

        // Step 1: Kirim prompt user ke Gemini dengan tool declaration
        $step1Response = Http::timeout(10)->post($url, [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $message]]],
            ],
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'tools' => $toolDeclarations,
        ]);

        if (!$step1Response->successful()) {
            throw new \Exception('Gemini API Error: ' . $step1Response->body());
        }

        $candidates = $step1Response->json('candidates.0.content.parts', []);
        $functionCall = null;
        foreach ($candidates as $part) {
            if (isset($part['functionCall'])) {
                $functionCall = $part['functionCall'];
                break;
            }
        }

        // Jika model tidak memanggil tool, ambil teks langsung
        if (!$functionCall) {
            $textReply = $step1Response->json('candidates.0.content.parts.0.text', '');
            return [
                'response' => $textReply ?: 'Halo! Ada yang bisa saya bantu terkait informasi persediaan stok telur asin?',
                'tool_called' => null,
                'tool_args' => null,
                'tool_result' => null,
            ];
        }

        // Step 2: Eksekusi tool lokal di server
        $funcName = $functionCall['name'];
        $funcArgs = $functionCall['args'] ?? [];
        $toolExecutionResult = $this->executeTool($funcName, $funcArgs);

        // Step 3: Kirim hasil tool kembali ke Gemini untuk sintesis bahasa alami
        $step2Response = Http::timeout(10)->post($url, [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $message]]],
                ['role' => 'model', 'parts' => [['functionCall' => $functionCall]]],
                [
                    'role' => 'function',
                    'parts' => [
                        [
                            'functionResponse' => [
                                'name' => $funcName,
                                'response' => $toolExecutionResult,
                            ],
                        ],
                    ],
                ],
            ],
            'system_instruction' => [
                'parts' => [['text' => $systemInstruction]],
            ],
            'tools' => $toolDeclarations,
        ]);

        $synthesizedText = $step2Response->json('candidates.0.content.parts.0.text', '');
        if (empty($synthesizedText)) {
            $synthesizedText = $this->formatFallbackText($funcName, $toolExecutionResult);
        }

        return [
            'response' => $synthesizedText,
            'tool_called' => $funcName,
            'tool_args' => $funcArgs,
            'tool_result' => $toolExecutionResult,
        ];
    }

    /**
     * Eksekusi server tool berdasarkan nama fungsi.
     */
    protected function executeTool(string $name, array $args): array
    {
        return match ($name) {
            'cek_stok' => $this->tools->cekStok($args['nama_produk'] ?? ''),
            'daftar_stok_menipis' => $this->tools->daftarStokMenipis(),
            'produk_terlaris' => $this->tools->produkTerlaris($args['periode'] ?? '30_hari'),
            'riwayat_barang_masuk' => $this->tools->riwayatBarangMasuk($args['nama_produk'] ?? null, (int) ($args['jumlah_hari'] ?? 7)),
            default => ['status' => 'error', 'message' => "Fungsi tool '{$name}' tidak dikenal."],
        };
    }

    /**
     * Resolusi lokal berbasis aturan cerdas jika API key belum dikonfigurasi.
     *
     * @return array{response: string, tool_called: ?string, tool_args: ?array, tool_result: ?array}
     */
    public function resolveLocally(string $message): array
    {
        $lower = strtolower($message);

        // Intent: Stok menipis / kritis / habis
        if (str_contains($lower, 'menipis') || str_contains($lower, 'habis') || str_contains($lower, 'peringatan') || str_contains($lower, 'kritis')) {
            $result = $this->tools->daftarStokMenipis();
            return [
                'response' => $this->formatFallbackText('daftar_stok_menipis', $result),
                'tool_called' => 'daftar_stok_menipis',
                'tool_args' => [],
                'tool_result' => $result,
            ];
        }

        // Intent: Produk terlaris / paling laku
        if (str_contains($lower, 'terlaris') || str_contains($lower, 'laku') || str_contains($lower, 'paling banyak keluar') || str_contains($lower, 'populer')) {
            $periode = '30_hari';
            if (str_contains($lower, '7 hari') || str_contains($lower, 'seminggu')) {
                $periode = '7_hari';
            } elseif (str_contains($lower, 'bulan ini')) {
                $periode = 'bulan_ini';
            }

            $result = $this->tools->produkTerlaris($periode);
            return [
                'response' => $this->formatFallbackText('produk_terlaris', $result),
                'tool_called' => 'produk_terlaris',
                'tool_args' => ['periode' => $periode],
                'tool_result' => $result,
            ];
        }

        // Intent: Riwayat barang masuk / pasokan
        if (str_contains($lower, 'barang masuk') || str_contains($lower, 'pasokan') || str_contains($lower, 'suplai') || str_contains($lower, 'panen')) {
            $result = $this->tools->riwayatBarangMasuk(null, 7);
            return [
                'response' => $this->formatFallbackText('riwayat_barang_masuk', $result),
                'tool_called' => 'riwayat_barang_masuk',
                'tool_args' => ['jumlah_hari' => 7],
                'tool_result' => $result,
            ];
        }

        // Intent: Cek stok produk tertentu
        if (str_contains($lower, 'stok') || str_contains($lower, 'cek') || str_contains($lower, 'berapa') || str_contains($lower, 'harga')) {
            // Bersihkan kata-kata umum untuk mengekstrak nama produk
            $cleaned = preg_replace('/(cek|stok|berapa|sisa|jumlah|ada|harga|telur|asin|tlr)/i', '', $message);
            $keyword = trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $cleaned));

            if (!empty($keyword) && strlen($keyword) >= 3) {
                $result = $this->tools->cekStok($keyword);
                return [
                    'response' => $this->formatFallbackText('cek_stok', $result),
                    'tool_called' => 'cek_stok',
                    'tool_args' => ['nama_produk' => $keyword],
                    'tool_result' => $result,
                ];
            }
        }

        // Salam / Bantuan Umum
        return [
            'response' => "Halo! Saya adalah Asisten AI SIM-STOK Telur Asin. Anda dapat menanyakan:\n"
                . "• \"Cek stok telur asin original\"\n"
                . "• \"Daftar produk yang stoknya menipis\"\n"
                . "• \"Apa saja produk terlaris 30 hari terakhir?\"\n"
                . "• \"Riwayat barang masuk seminggu terakhir\"",
            'tool_called' => null,
            'tool_args' => null,
            'tool_result' => null,
        ];
    }

    /**
     * Format teks rapi dari hasil eksekusi tool lokal.
     */
    protected function formatFallbackText(string $toolName, array $result): string
    {
        switch ($toolName) {
            case 'cek_stok':
                if (empty($result['data'])) {
                    return $result['message'] ?? 'Produk tidak ditemukan dalam katalog persediaan.';
                }

                $lines = ["Berikut hasil pencarian stok produk:"];
                foreach ($result['data'] as $p) {
                    $status = $p['status_stok'];
                    $badge = $status === 'Aman' ? '✅ Aman' : ($status === 'Menipis' ? '⚠️ Menipis' : '❌ Habis');
                    $lines[] = "• **{$p['nama']}** ({$p['sku']})\n  Stok: **{$p['stok']} {$p['satuan']}** (Min: {$p['batas_minimum']}) | Status: {$badge} | Harga: Rp " . number_format($p['harga_jual'], 0, ',', '.');
                }
                return implode("\n", $lines);

            case 'daftar_stok_menipis':
                if (empty($result['data'])) {
                    return "✅ " . ($result['message'] ?? 'Seluruh persediaan aman. Tidak ada produk yang menipis.');
                }

                $lines = ["⚠️ Ditemukan **{$result['total_menipis']} varian** produk dengan stok menipis / mencapai batas minimum:"];
                foreach ($result['data'] as $item) {
                    $lines[] = "• **{$item['nama']}** ({$item['sku']}): Sisa **{$item['stok_sekarang']} {$item['satuan']}** (Batas Min: {$item['batas_minimum']}) &mdash; [{$item['kondisi']}]";
                }
                return implode("\n", $lines);

            case 'produk_terlaris':
                if (empty($result['data'])) {
                    return $result['message'] ?? 'Belum ada data barang keluar.';
                }

                $lines = ["🏆 **5 Produk Terlaris ({$result['periode']})**:"];
                foreach ($result['data'] as $item) {
                    $lines[] = "{$item['ranking']}. **{$item['nama_produk']}**: {$item['total_terjual']} {$item['satuan']}";
                }
                return implode("\n", $lines);

            case 'riwayat_barang_masuk':
                if (empty($result['data'])) {
                    return $result['message'] ?? 'Tidak ada pasokan barang masuk.';
                }

                $lines = ["📦 **Riwayat Barang Masuk ({$result['periode']})**:"];
                foreach ($result['data'] as $item) {
                    $lines[] = "• [{$item['tanggal']}] **{$item['nama_produk']}** (+{$item['jumlah']} {$item['satuan']}) dari {$item['sumber']}";
                }
                return implode("\n", $lines);

            default:
                return 'Informasi telah diproses oleh sistem.';
        }
    }
}
