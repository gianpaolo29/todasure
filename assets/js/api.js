// ============================================
// TODASURE - API Helper Functions
// ============================================

const API_BASE = '../api';

async function apiRequest(endpoint, method = 'GET', data = null) {
    const options = {
        method,
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin'
    };
    if (data && method !== 'GET') {
        options.body = JSON.stringify(data);
    }

    // Support both relative (trips/gps.php) and full paths (../api/trips/gps.php)
    const base = endpoint.startsWith('../') || endpoint.startsWith('/') ? '' : `${API_BASE}/`;
    const url = method === 'GET' && data
        ? `${base}${endpoint}?${new URLSearchParams(data)}`
        : `${base}${endpoint}`;

    const response = await fetch(url, options);
    const result = await response.json();

    if (!response.ok) {
        throw new Error(result.error || 'Request failed');
    }
    return result;
}

// Auth
const auth = {
    login: (username, password) => apiRequest('auth/login.php', 'POST', { username, password }),
    logout: () => apiRequest('auth/logout.php', 'POST'),
    session: () => apiRequest('auth/session.php')
};

// Drivers
const drivers = {
    list: (params) => apiRequest('drivers/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('drivers/index.php?id=' + id),
    create: (data) => apiRequest('drivers/index.php', 'POST', data),
    update: (id, data) => apiRequest('drivers/index.php?id=' + id, 'PUT', data),
    delete: (id) => apiRequest('drivers/index.php?id=' + id, 'DELETE')
};

// Tricycles
const tricycles = {
    list: (params) => apiRequest('tricycles/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('tricycles/index.php?id=' + id),
    create: (data) => apiRequest('tricycles/index.php', 'POST', data),
    update: (id, data) => apiRequest('tricycles/index.php?id=' + id, 'PUT', data),
    delete: (id) => apiRequest('tricycles/index.php?id=' + id, 'DELETE')
};

// TODAs
const todas = {
    list: () => apiRequest('toda/index.php'),
    get: (id) => apiRequest('toda/index.php?id=' + id),
    create: (data) => apiRequest('toda/index.php', 'POST', data),
    update: (id, data) => apiRequest('toda/index.php?id=' + id, 'PUT', data)
};

// Trips
const trips = {
    list: (params) => apiRequest('trips/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('trips/index.php?id=' + id),
    create: (data) => apiRequest('trips/index.php', 'POST', data),
    update: (id, data) => apiRequest('trips/index.php?id=' + id, 'PUT', data)
};

// Fare Rates
const fares = {
    list: (params) => apiRequest('fares/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('fares/index.php?id=' + id),
    create: (data) => apiRequest('fares/index.php', 'POST', data),
    update: (id, data) => apiRequest('fares/index.php?id=' + id, 'PUT', data)
};

// Complaints
const complaints = {
    list: (params) => apiRequest('complaints/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('complaints/index.php?id=' + id),
    create: (data) => apiRequest('complaints/index.php', 'POST', data),
    update: (id, data) => apiRequest('complaints/index.php?id=' + id, 'PUT', data)
};

// Violations
const violations = {
    list: (params) => apiRequest('violations/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('violations/index.php?id=' + id),
    create: (data) => apiRequest('violations/index.php', 'POST', data),
    update: (id, data) => apiRequest('violations/index.php?id=' + id, 'PUT', data)
};

// Users
const users = {
    list: (params) => apiRequest('users/index.php' + (params ? '?' + new URLSearchParams(params) : '')),
    get: (id) => apiRequest('users/index.php?id=' + id),
    update: (id, data) => apiRequest('users/index.php?id=' + id, 'PUT', data)
};

// Barangays
const barangays = {
    list: () => apiRequest('barangays/index.php'),
    create: (data) => apiRequest('barangays/index.php', 'POST', data)
};

// Notifications
const notifications = {
    list: () => apiRequest('notifications/index.php'),
    markRead: (id) => apiRequest('notifications/index.php?id=' + id, 'PUT', {}),
    markAllRead: () => apiRequest('notifications/index.php', 'PUT', { mark_all_read: true })
};

// Bookings
const bookings = {
    create: (data) => apiRequest('bookings/index.php', 'POST', data),
    get: (id) => apiRequest('bookings/index.php?id=' + id),
    mine: () => apiRequest('bookings/index.php?action=mine'),
    pending: (lat, lng) => apiRequest('bookings/index.php?action=pending' + (lat ? '&lat='+lat+'&lng='+lng : '')),
    driverActive: () => apiRequest('bookings/index.php?action=driver-active'),
    update: (id, data) => apiRequest('bookings/index.php?id=' + id, 'PUT', data),
    list: (params) => apiRequest('bookings/index.php' + (params ? '?' + new URLSearchParams(params) : ''))
};

// Dashboard
const dashboard = {
    adminStats: () => apiRequest('dashboard/stats.php'),
    driverStats: () => apiRequest('dashboard/driver-stats.php')
};

// Utility functions
function showAlert(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    const container = document.querySelector('.main-content') || document.body;
    container.prepend(alertDiv);
    setTimeout(() => alertDiv.remove(), 4000);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit'
    });
}

function formatCurrency(amount) {
    return 'PHP ' + parseFloat(amount || 0).toFixed(2);
}

function badgeClass(status) {
    return 'badge badge-' + status;
}
