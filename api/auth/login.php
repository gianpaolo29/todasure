<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (getMethod() !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = getInput();
$email = $input['email'] ?? '';
$password = $input['password'] ?? '';

if (empty($email) || empty($password)) {
    jsonResponse(['error' => 'Email and password are required'], 400);
}

$pdo = getConnection();
$stmt = $pdo->prepare("SELECT id, username, email, password, first_name, last_name, role, status, barangay_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(['error' => 'Invalid email or password'], 401);
}

if ($user['status'] !== 'active') {
    jsonResponse(['error' => 'Account is not active. Please contact admin.'], 403);
}

session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['email'] = $user['email'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['barangay_id'] = $user['barangay_id'] ?? null;

$response = [
    'message' => 'Login successful',
    'user' => [
        'id' => $user['id'],
        'username' => $user['username'],
        'email' => $user['email'],
        'first_name' => $user['first_name'],
        'last_name' => $user['last_name'],
        'role' => $user['role'],
        'barangay_id' => $user['barangay_id'] ?? null
    ]
];

// If driver, include driver info
if ($user['role'] === 'driver') {
    $stmt = $pdo->prepare("SELECT d.id as driver_id, d.first_name, d.last_name, t.id as toda_id, t.name as toda_name
                           FROM drivers d LEFT JOIN todas t ON d.toda_id = t.id
                           WHERE d.user_id = ?");
    $stmt->execute([$user['id']]);
    $driver = $stmt->fetch();

    // Auto-create driver record if missing
    if (!$driver) {
        $stmt = $pdo->prepare("INSERT INTO drivers (user_id, first_name, last_name, contact_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $user['first_name'], $user['last_name'], $user['phone'] ?? null]);
        $driverId = $pdo->lastInsertId();
        $driver = ['driver_id' => $driverId, 'first_name' => $user['first_name'], 'last_name' => $user['last_name'], 'toda_id' => null, 'toda_name' => null];
    }

    $response['driver'] = $driver;
    $_SESSION['driver_id'] = $driver['driver_id'];
}

// Set redirect based on role
switch ($user['role']) {
    case 'admin':
        $response['redirect'] = '/TodaShare/admin/dashboard.html';
        break;
    case 'driver':
        $response['redirect'] = '/TodaShare/driver/dashboard.html';
        break;
    case 'barangay':
        $response['redirect'] = '/TodaShare/barangay/dashboard.html';
        break;
    case 'passenger':
    default:
        $response['redirect'] = '/TodaShare/passenger/dashboard.html';
        break;
}

jsonResponse($response);
