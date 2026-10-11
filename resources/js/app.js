import './bootstrap';

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
