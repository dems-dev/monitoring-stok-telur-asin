<div x-data="chatbotWidget()" x-init="initChat()" class="no-print">
    <!-- Tombol Mengambang (Pojok Kanan Bawah) -->
    <button @click="toggleChat()"
            type="button"
            class="fixed bottom-6 right-6 z-40 w-14 h-14 rounded-full bg-amber-500 hover:bg-amber-600 active:scale-95 text-white shadow-xl shadow-amber-500/30 flex items-center justify-center text-2xl transition-all duration-200 group focus:outline-none"
            title="Tanya AI Asisten Stok Telur Asin">
        <i class="fa-solid fa-robot transition-transform group-hover:scale-110" x-show="!isOpen"></i>
        <i class="fa-solid fa-xmark text-xl" x-show="isOpen" style="display: none;"></i>
        <!-- Pulse Indicator -->
        <span class="absolute top-1 right-1 w-3.5 h-3.5 bg-emerald-500 border-2 border-white rounded-full"></span>
    </button>

    <!-- Jendela Pop-up Percakapan Chatbot -->
    <div x-show="isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-6 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-6 scale-95"
         style="display: none;"
         class="fixed bottom-22 right-6 z-50 w-96 max-w-[calc(100vw-2rem)] h-[520px] max-h-[80vh] bg-white rounded-3xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden">

        <!-- Header Chatbot -->
        <div class="px-4 py-3.5 bg-gradient-to-r from-amber-500 to-amber-600 text-white flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-xl bg-white/20 backdrop-blur-xs flex items-center justify-center text-lg">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div>
                    <div class="text-xs font-bold leading-tight flex items-center gap-1.5">
                        <span>AI Asisten Stok</span>
                        <span class="text-[9px] font-semibold px-1.5 py-0.2 rounded-full bg-emerald-500/80 text-white">Aktif</span>
                    </div>
                    <p class="text-[10px] text-amber-100 mt-0.5">Informasi Stok &middot; Hanya-Baca</p>
                </div>
            </div>

            <div class="flex items-center gap-1">
                <button @click="clearChat()" title="Bersihkan Obrolan"
                        class="p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                    <i class="fa-regular fa-trash-can text-xs"></i>
                </button>
                <button @click="isOpen = false" title="Tutup"
                        class="p-1.5 text-white/80 hover:text-white hover:bg-white/10 rounded-lg transition-colors">
                    <i class="fa-solid fa-xmark text-sm"></i>
                </button>
            </div>
        </div>

        <!-- Area Pesan Obrolan -->
        <div id="chatbot-messages" class="flex-1 p-4 overflow-y-auto space-y-3 custom-scrollbar bg-slate-50/60">
            <!-- Pesan Sambutan Bot -->
            <div class="flex items-start gap-2">
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5">
                    <i class="fa-solid fa-egg"></i>
                </div>
                <div class="bg-white border border-slate-200/90 rounded-2xl rounded-tl-xs p-3 text-xs text-slate-800 shadow-2xs max-w-[85%] space-y-2">
                    <p class="font-medium">Halo, <b>{{ auth()->user()->name }}</b>! Saya asisten virtual stok telur asin. Silakan tanyakan informasi persediaan atau klik saran cepat berikut:</p>
                    <div class="flex flex-wrap gap-1.5 pt-1">
                        <button type="button" @click="sendQuick('Cek stok telur asin original')"
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-800 rounded-lg transition-colors border border-slate-200/80">
                            🥚 Cek Stok Original
                        </button>
                        <button type="button" @click="sendQuick('Daftar stok menipis')"
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-rose-100 text-slate-700 hover:text-rose-800 rounded-lg transition-colors border border-slate-200/80">
                            ⚠️ Stok Menipis
                        </button>
                        <button type="button" @click="sendQuick('Produk terlaris 30 hari terakhir')"
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-amber-100 text-slate-700 hover:text-amber-800 rounded-lg transition-colors border border-slate-200/80">
                            🏆 Produk Terlaris
                        </button>
                        <button type="button" @click="sendQuick('Riwayat barang masuk seminggu terakhir')"
                                class="px-2.5 py-1 text-[11px] font-semibold bg-slate-100 hover:bg-emerald-100 text-slate-700 hover:text-emerald-800 rounded-lg transition-colors border border-slate-200/80">
                            📦 Pasokan Masuk
                        </button>
                    </div>
                </div>
            </div>

            <!-- Loop Riwayat Pesan -->
            <template x-for="(msg, index) in messages" :key="index">
                <div>
                    <!-- Pesan User (Kanan) -->
                    <template x-if="msg.sender === 'user'">
                        <div class="flex justify-end mb-2">
                            <div class="bg-amber-500 text-white rounded-2xl rounded-tr-xs px-3.5 py-2 text-xs max-w-[82%] shadow-2xs break-words font-medium">
                                <span x-text="msg.text"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Pesan Bot (Kiri) -->
                    <template x-if="msg.sender === 'bot'">
                        <div class="flex items-start gap-2 mb-2">
                            <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5">
                                <i class="fa-solid fa-robot"></i>
                            </div>
                            <!-- x-html aman: formatBot() meng-escape seluruh HTML sebelum menambah <strong>. -->
                            <div class="bg-white border border-slate-200/90 rounded-2xl rounded-tl-xs p-3 text-xs text-slate-800 shadow-2xs max-w-[85%] whitespace-pre-line leading-relaxed break-words"
                                 x-html="formatBot(msg.text)">
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            <!-- Indikator Loading Bot Sedang Mengetik -->
            <div x-show="isLoading" class="flex items-start gap-2" style="display: none;">
                <div class="w-7 h-7 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center text-xs shrink-0 mt-0.5">
                    <i class="fa-solid fa-robot"></i>
                </div>
                <div class="bg-white border border-slate-200 rounded-2xl rounded-tl-xs px-3.5 py-2.5 text-xs text-slate-500 shadow-2xs flex items-center gap-1.5">
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-bounce"></span>
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                    <span class="w-1.5 h-1.5 bg-amber-500 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                    <span class="text-[11px] text-slate-400 ml-1">Mencari data persediaan...</span>
                </div>
            </div>
        </div>

        <!-- Input Box & Tombol Kirim -->
        <form @submit.prevent="sendMessage()" class="p-3 bg-white border-t border-slate-100 flex items-center gap-2">
            @csrf
            <input type="text"
                   x-model="inputText"
                   :disabled="isLoading"
                   placeholder="Tanyakan stok, varian, atau penjualan..."
                   class="flex-1 px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-amber-500 focus:bg-white transition-all disabled:opacity-50">
            <button type="submit"
                    :disabled="isLoading || !inputText.trim()"
                    class="w-8 h-8 rounded-xl bg-amber-500 hover:bg-amber-600 disabled:opacity-50 text-white flex items-center justify-center text-xs transition-colors shadow-xs">
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>
</div>

<script>
function chatbotWidget() {
    return {
        isOpen: false,
        isLoading: false,
        inputText: '',
        messages: [],

        initChat() {
            // Load history from server
            fetch('{{ route('chatbot.history') }}')
                .then(r => r.json())
                .then(data => {
                    if (data && data.history) {
                        data.history.forEach(item => {
                            this.messages.push({ sender: 'user', text: item.message });
                            this.messages.push({ sender: 'bot', text: item.response });
                        });
                        this.scrollToBottom();
                    }
                })
                .catch(() => {});
        },

        toggleChat() {
            this.isOpen = !this.isOpen;
            if (this.isOpen) {
                this.scrollToBottom();
            }
        },

        sendQuick(text) {
            this.inputText = text;
            this.sendMessage();
        },

        sendMessage() {
            const text = this.inputText.trim();
            if (!text || this.isLoading) return;

            // Push user message to local state
            this.messages.push({ sender: 'user', text: text });
            this.inputText = '';
            this.isLoading = true;
            this.scrollToBottom();

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '{{ csrf_token() }}';

            fetch('{{ route('chatbot.message') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({ message: text }),
            })
            .then(res => res.json())
            .then(data => {
                this.isLoading = false;
                const reply = data.reply || 'Maaf, tidak ada tanggapan dari sistem.';
                this.messages.push({ sender: 'bot', text: reply });
                this.scrollToBottom();
            })
            .catch(() => {
                this.isLoading = false;
                this.messages.push({
                    sender: 'bot',
                    text: 'Maaf, terjadi gangguan koneksi ke server. Silakan coba sesaat lagi.'
                });
                this.scrollToBottom();
            });
        },

        clearChat() {
            if (!confirm('Hapus seluruh riwayat percakapan?')) return;

            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || '{{ csrf_token() }}';

            fetch('{{ route('chatbot.clear') }}', {
                method: 'DELETE',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
            })
            .then(() => {
                this.messages = [];
            });
        },

        /**
         * Ubah jawaban bot menjadi HTML yang aman: escape semua karakter HTML
         * lebih dulu, baru format Markdown sederhana (**tebal**, poin "* ").
         */
        formatBot(text) {
            const escaped = String(text ?? '')
                // Riwayat chat lama masih menyimpan entitas &mdash; mentah.
                .replace(/&mdash;/g, '-')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');

            return escaped
                .replace(/\*\*(.+?)\*\*/g, '<strong>$1</strong>')
                .replace(/^\* /gm, '• ');
        },

        scrollToBottom() {
            this.$nextTick(() => {
                const el = document.getElementById('chatbot-messages');
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    }
}
</script>
