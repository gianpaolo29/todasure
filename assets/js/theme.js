// ── Theme Toggle (Dark/Light Mode) ──
(function() {
    const STORAGE_KEY = 'todasure-theme';

    function getPreferred() {
        return localStorage.getItem(STORAGE_KEY) || 'dark';
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
        localStorage.setItem(STORAGE_KEY, theme);
        // Update all toggle buttons on the page
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            const icon = btn.querySelector('.icon i');
            const label = btn.querySelector('.label');
            if (icon) icon.className = theme === 'dark' ? 'fas fa-sun' : 'fas fa-moon';
            if (label) label.textContent = theme === 'dark' ? 'Light Mode' : 'Dark Mode';
        });
    }

    function toggle() {
        const current = getPreferred();
        applyTheme(current === 'dark' ? 'light' : 'dark');
    }

    // Apply immediately to prevent flash
    applyTheme(getPreferred());

    // Expose globally
    window.themeToggle = toggle;

    // Auto-bind toggle buttons once DOM is ready
    function bindButtons() {
        document.querySelectorAll('.theme-toggle').forEach(btn => {
            btn.removeEventListener('click', toggle);
            btn.addEventListener('click', toggle);
        });
        applyTheme(getPreferred());
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindButtons);
    } else {
        bindButtons();
    }
})();
