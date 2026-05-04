<?php
// Common helper functions

function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function getInput() {
    $json = file_get_contents('php://input');
    return json_decode($json, true) ?: [];
}

function requireAuth() {
    session_start();
    if (!isset($_SESSION['user_id'])) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $_SESSION;
}

function requireAdmin() {
    $session = requireAuth();
    if ($session['role'] !== 'admin') {
        jsonResponse(['error' => 'Forbidden'], 403);
    }
    return $session;
}

function requireRole($roles) {
    $session = requireAuth();
    if (!in_array($session['role'], (array)$roles)) {
        jsonResponse(['error' => 'Forbidden. Required role: ' . implode(' or ', (array)$roles)], 403);
    }
    return $session;
}

function getMethod() {
    return $_SERVER['REQUEST_METHOD'];
}

function getId() {
    return isset($_GET['id']) ? (int)$_GET['id'] : null;
}

// Calculate fare based on distance and fare rate
function calculateFare($distanceKm, $baseFare, $baseDistance, $perKmRate) {
    $distanceKm = floatval($distanceKm ?? 0);
    $baseFare = floatval($baseFare ?? 0);
    $baseDistance = floatval($baseDistance ?? 0);
    $perKmRate = floatval($perKmRate ?? 0);

    if ($baseFare <= 0) return 0;
    if ($distanceKm <= $baseDistance) {
        return $baseFare;
    }
    $extraDistance = $distanceKm - $baseDistance;
    return $baseFare + ($extraDistance * $perKmRate);
}
