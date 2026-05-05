// ============================================
// TODASURE - Shared Admin Layout
// Injects consistent sidebar, header, and
// common JS for all admin pages
// ============================================

(function() {
    // Detect current page for active nav
    const path = window.location.pathname;
    const currentPage = path.substring(path.lastIndexOf('/') + 1) || 'dashboard.html';

    function navClass(page) {
        return currentPage === page ? 'active' : '';
    }

    // Build sidebar + header HTML
    const layoutPrefix = `
    <button class="sidebar-toggle" id="sidebarToggle"><i class="fa-solid fa-bars"></i></button>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="layout">
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <div class="logo-icon"><img src="../todasure-logo.jpg" style="width:100%;height:100%;object-fit:cover;border-radius:inherit"></div>
                <div>
                    <h2>TODASURE</h2>
                    <span>Admin Panel</span>
                </div>
            </div>
            <nav>
                <a href="dashboard.html" class="${navClass('dashboard.html')}"><i class="fa-solid fa-gauge icon"></i> Dashboard</a>
                <a href="drivers.html" class="${navClass('drivers.html')}"><i class="fa-solid fa-id-card icon"></i> Drivers</a>
                <a href="tricycles.html" class="${navClass('tricycles.html')}"><i class="fa-solid fa-motorcycle icon"></i> Tricycles</a>
                <a href="trips.html" class="${navClass('trips.html')}"><i class="fa-solid fa-route icon"></i> Trips</a>
                <a href="fares.html" class="${navClass('fares.html')}"><i class="fa-solid fa-peso-sign icon"></i> Fare Rates</a>
                <a href="complaints.html" class="${navClass('complaints.html')}"><i class="fa-solid fa-triangle-exclamation icon"></i> Complaints</a>
                <a href="violations.html" class="${navClass('violations.html')}"><i class="fa-solid fa-gavel icon"></i> Violations</a>
                <a href="officials.html" class="${navClass('officials.html')}"><i class="fa-solid fa-user-tie icon"></i> Brgy Officials</a>
            </nav>
        </aside>
        <main class="main-content">
            <div class="top-header">
                <span class="page-title" id="pageTitle"></span>
                <div class="header-search">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search drivers, trips, plates..." id="globalSearch">
                </div>
                <div class="header-actions">
                    <button class="header-icon-btn" id="notifBtn" title="Notifications">
                        <i class="fa-solid fa-bell"></i>
                        <span class="notif-dot" id="notifDot" style="display:none"></span>
                    </button>
                    <div class="profile-dropdown">
                        <button class="profile-btn" id="profileBtn">
                            <div class="profile-avatar" id="profileInitial">A</div>
                            <div class="profile-info">
                                <div class="profile-name" id="profileName">Admin</div>
                                <div class="profile-role">Administrator</div>
                            </div>
                            <i class="fa-solid fa-chevron-down chevron"></i>
                        </button>
                        <div class="dropdown-menu" id="profileMenu">
                            <a href="#"><i class="fa-solid fa-user"></i> Account Profile</a>
                            <div class="divider"></div>
                            <button class="danger" onclick="handleLogout()"><i class="fa-solid fa-right-from-bracket"></i> Logout</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="page-body">
    `;

    const layoutSuffix = `
            </div>
        </main>
    </div>
    `;

    // Inject layout around page content
    const adminContent = document.getElementById('admin-content');
    if (adminContent) {
        const pageTitle = adminContent.getAttribute('data-page-title') || 'Dashboard';
        document.body.innerHTML = layoutPrefix + adminContent.outerHTML + layoutSuffix;
        document.getElementById('pageTitle').textContent = pageTitle;
    }

    // ── Sidebar Toggle ──
    document.getElementById('sidebarToggle').addEventListener('click', function() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    });
    document.getElementById('sidebarOverlay').addEventListener('click', function() {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    });

    // ── Profile Dropdown ──
    document.getElementById('profileBtn').addEventListener('click', function(e) {
        e.stopPropagation();
        document.getElementById('profileMenu').classList.toggle('show');
    });
    document.addEventListener('click', function() {
        document.getElementById('profileMenu').classList.remove('show');
    });

    // ── Load Profile Name ──
    fetch('/TodaShare/api/auth/session.php', { credentials: 'same-origin' })
        .then(function(r) { return r.json(); })
        .then(function(s) {
            var name = ((s.first_name || '') + ' ' + (s.last_name || '')).trim() || 'Admin';
            var el = document.getElementById('profileName');
            if (el) el.textContent = name;
            var ini = document.getElementById('profileInitial');
            if (ini) ini.textContent = (s.first_name || 'A')[0].toUpperCase();
        }).catch(function() {});

    // ── Notifications ──
    document.getElementById('notifBtn').addEventListener('click', function() {
        Swal.fire({
            title: 'Notifications',
            html: '<p style="color:#6b7280;">No new notifications</p>',
            icon: 'info',
            confirmButtonColor: '#7c3aed',
            confirmButtonText: 'OK'
        });
    });

    // ── SweetAlert showAlert override ──
    window.showAlert = function(message, type) {
        type = type || 'success';
        Swal.mixin({
            toast: true, position: 'top-end', showConfirmButton: false,
            timer: 3000, timerProgressBar: true
        }).fire({ icon: type === 'error' ? 'error' : 'success', title: message });
    };

    // ── Logout ──
    window.handleLogout = function() {
        Swal.fire({
            title: 'Logout?',
            text: 'Are you sure you want to sign out?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#9ca3af',
            confirmButtonText: 'Yes, logout'
        }).then(function(result) {
            if (result.isConfirmed) {
                fetch('/TodaShare/api/auth/logout.php', { method: 'POST', credentials: 'same-origin' })
                    .finally(function() {
                        localStorage.removeItem('user');
                        window.location.replace('/TodaShare/login.html');
                    });
            }
        });
    };
})();
