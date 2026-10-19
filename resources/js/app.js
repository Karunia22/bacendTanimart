// Toggle tema gelap/terang (M3 dark scheme).
// Preferensi disimpan di localStorage; dibaca saat halaman dimuat.
window.TaniMartTheme = {
    init() {
        const saved = localStorage.getItem('tanimart-admin-theme');
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        if (saved === 'dark' || (!saved && prefersDark)) {
            document.documentElement.classList.add('dark');
        }
    },
    toggle() {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('tanimart-admin-theme', isDark ? 'dark' : 'light');
    },
};

window.TaniMartTheme.init();
