// resources/js/theme.js
// Di app.js tambahkan:  import './theme';
// Livewire 4 sudah membawa Alpine, jadi JANGAN import/start Alpine lagi di sini.

document.addEventListener('alpine:init', () => {
    Alpine.data('theme', () => ({
        darkMode: false,

        init() {
            try {
                this.darkMode = localStorage.getItem('darkMode') === 'true';
            } catch (e) {
                this.darkMode = false;
            }
            this.applyTheme();
        },

        toggleDarkMode() {
            this.darkMode = !this.darkMode;
            try {
                localStorage.setItem('darkMode', String(this.darkMode));
            } catch (e) {
                // Tema tetap berubah selama halaman terbuka.
            }
            this.applyTheme();
        },

        applyTheme() {
            document.documentElement.classList.toggle('dark', this.darkMode);
            document.documentElement.setAttribute('data-theme', this.darkMode ? 'dark' : 'light');
        },
    }));
});
