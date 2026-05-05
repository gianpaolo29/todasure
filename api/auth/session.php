<?php
require_once __DIR__ . '/../config/helpers.php';

$session = requireAuth();
jsonResponse([
    'user_id' => $session['user_id'],
    'username' => $session['username'],
    'email' => $session['email'] ?? null,
    'first_name' => $session['first_name'] ?? null,
    'last_name' => $session['last_name'] ?? null,
    'phone' => $session['phone'] ?? null,
    'role' => $session['role'],
    'driver_id' => $session['driver_id'] ?? null,
    'barangay_id' => $session['barangay_id'] ?? null
]);
