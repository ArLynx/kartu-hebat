document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-auto-dismiss]').forEach((element) => {
        window.setTimeout(() => element.remove(), 5000);
    });
});
