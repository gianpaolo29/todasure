<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (getMethod() !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$input = getInput();

// Validate required fields
$required = ['first_name', 'last_name', 'email', 'phone', 'password', 'confirm_password'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        jsonResponse(['error' => ucfirst(str_replace('_', ' ', $field)) . ' is required'], 400);
    }
}

$firstName = trim($input['first_name']);
$lastName = trim($input['last_name']);
$email = trim($input['email']);
$phone = trim($input['phone']);
$password = $input['password'];
$confirmPassword = $input['confirm_password'];

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['error' => 'Invalid email format'], 400);
}

// Validate password match
if ($password !== $confirmPassword) {
    jsonResponse(['error' => 'Passwords do not match'], 400);
}

// Validate password strength (min 8 chars)
if (strlen($password) < 8) {
    jsonResponse(['error' => 'Password must be at least 8 characters'], 400);
}

$pdo = getConnection();

// Check if email already exists
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    jsonResponse(['error' => 'Email already registered'], 409);
}

// Generate username from email
$username = explode('@', $email)[0];
$stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmt->execute([$username]);
if ($stmt->fetch()) {
    $username = $username . rand(100, 999);
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert user with default role 'passenger'
$stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, role, status) VALUES (?, ?, ?, ?, ?, ?, 'passenger', 'active')");
$stmt->execute([$username, $email, $hashedPassword, $firstName, $lastName, $phone]);

$userId = $pdo->lastInsertId();

jsonResponse([
    'success' => true,
    'message' => 'Registration successful',
    'user' => [
        'id' => $userId,
        'username' => $username,
        'email' => $email,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'role' => 'passenger'
    ]
], 201);
