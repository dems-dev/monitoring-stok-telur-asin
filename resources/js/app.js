import './bootstrap';
import Alpine from 'alpinejs';

// Ikon dan font dibundel lokal (bukan CDN) agar aplikasi tetap rapi saat offline.
import '@fortawesome/fontawesome-free/css/all.css';
import '@fontsource/plus-jakarta-sans/300.css';
import '@fontsource/plus-jakarta-sans/400.css';
import '@fontsource/plus-jakarta-sans/500.css';
import '@fontsource/plus-jakarta-sans/600.css';
import '@fontsource/plus-jakarta-sans/700.css';
import '@fontsource/plus-jakarta-sans/800.css';

window.Alpine = Alpine;
Alpine.start();

/**
 * Konfirmasi untuk aksi yang menghapus data: form cukup diberi
 * atribut data-confirm="pesan", tanpa onclick di tiap tombol.
 */
document.addEventListener('submit', (event) => {
    const form = event.target;

    if (form instanceof HTMLFormElement && form.dataset.confirm) {
        if (! window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    }
});
