document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        window.setTimeout(() => element.remove(), 5000);
    });

    // Konfirmasi via atribut data-confirm. Nilainya di-escape HTML di Blade,
    // jadi teks pengguna (mis. nama operator) tidak pernah masuk ke string JS inline.
    document.addEventListener('submit', (event) => {
        const form = event.target.closest('form');

        if (form?.hasAttribute('data-confirm') && !window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});
