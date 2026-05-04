<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

if (getMethod() !== 'POST') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

$session = requireAuth();
$input = getInput();

$firstName = trim($input['first_name'] ?? '');
$lastName = trim($input['last_name'] ?? '');
$phone = trim($input['phone'] ?? '');

if (empty($firstName)) {
    jsonResponse(['error' => 'First name is required'], 400);
}

$pdo = getConnection();
$stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, phone = ? WHERE id = ?");
$stmt->execute([$firstName, $lastName, $phone, $session['user_id']]);

// Update session
$_SESSION['first_name'] = $firstName;
$_SESSION['last_name'] = $lastName;

jsonResponse([
    'message' => 'Profile updated',
    'user' => [
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $phone
    ]
]);
