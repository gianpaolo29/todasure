// ============================================
// Session Guard - prevents back-button access
// after logout by checking session on every page load
// ============================================

(function() {
    // Prevent browser from caching this page
    if (window.history && window.history.replaceState) {
        // Replace current history entry so back button can't return here after logout
        window.history.replaceState(null, '', window.location.href);
    }

    // Check session on every page load (including back-button navigations)
    window.addEventListener('pageshow', function(event) {
        // persisted = true means page was loaded from back/forward cache
        if (event.persisted) {
            checkSession();
        }
    });

    function checkSession() {
        fetch('/TodaShare/api/auth/session.php', { credentials: 'same-origin' })
            .then(function(res) {
                if (!res.ok) {
                    localStorage.removeItem('user');
                    window.location.replace('/TodaShare/login.html');
                }
            })
            .catch(function() {
                localStorage.removeItem('user');
                window.location.replace('/TodaShare/login.html');
            });
    }

    // Also check immediately on load
    checkSession();
})();
