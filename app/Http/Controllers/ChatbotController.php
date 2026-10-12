<?php

namespace App\Http\Controllers;

use App\Models\ChatLog;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class ChatbotController extends Controller
{
    public function __construct(
        protected ChatbotService $chatbot
    ) {}

    /**
     * Kirim pesan ke AI chatbot persediaan.
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'message' => ['required', 'string', 'max:500'],
        ]);

        $user = $request->user();
        $rateLimitKey = 'chatbot:' . $user->id;

        // Rate limit: Maksimal 20 pesan per menit per user
        if (RateLimiter::tooManyAttempts($rateLimitKey, 20)) {
            $seconds = RateLimiter::availableIn($rateLimitKey);

            return response()->json([
                'error' => 'rate_limited',
                'reply' => "Pesan Anda terlalu cepat. Mohon tunggu {$seconds} detik sebelum mengirim pesan kembali.",
            ], 429);
        }

        RateLimiter::hit($rateLimitKey, 60);

        try {
            $result = $this->chatbot->handle($user, $request->input('message'));

            return response()->json([
                'reply' => $result['response'],
                'tool_called' => $result['tool_called'],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'reply' => 'Maaf, terjadi gangguan saat memproses pertanyaan Anda. Silakan coba sesaat lagi.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ambil riwayat percakapan pengguna saat ini (terakhir 20).
     */
    public function history(Request $request): JsonResponse
    {
        $logs = ChatLog::where('user_id', $request->user()->id)
            ->latest()
            ->limit(20)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'history' => $logs->map(fn (ChatLog $log) => [
                'id' => $log->id,
                'message' => $log->message,
                'response' => $log->response,
                'time' => $log->created_at->format('H:i'),
            ]),
        ]);
    }

    /**
     * Hapus riwayat percakapan pengguna saat ini.
     */
    public function clearHistory(Request $request): JsonResponse
    {
        ChatLog::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Riwayat chat telah dibersihkan.',
        ]);
    }
}
