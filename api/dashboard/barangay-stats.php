<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$session = requireAuth();

if ($session['role'] !== 'barangay' && $session['role'] !== 'admin') {
    jsonResponse(['error' => 'Forbidden'], 403);
}

$barangayId = $session['barangay_id'] ?? null;
if (!$barangayId && $session['role'] === 'barangay') {
    jsonResponse(['error' => 'No barangay assigned to this account'], 400);
}

$pdo = getConnection();
$stats = [];

// Barangay name
$stmt = $pdo->prepare("SELECT name FROM barangays WHERE id = ?");
$stmt->execute([$barangayId]);
$brgy = $stmt->fetch();
$stats['barangay_name'] = $brgy ? $brgy['name'] : 'Unknown';

// Drivers in this barangay (via TODA → barangay)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM drivers d
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ? AND d.status = 'active'
");
$stmt->execute([$barangayId]);
$stats['total_drivers'] = $stmt->fetch()['total'];

// Tricycles in this barangay
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM tricycles tr
    JOIN drivers d ON tr.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ? AND tr.status = 'active'
");
$stmt->execute([$barangayId]);
$stats['total_tricycles'] = $stmt->fetch()['total'];

// TODAs in this barangay
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM todas WHERE barangay_id = ? AND status = 'active'");
$stmt->execute([$barangayId]);
$stats['total_todas'] = $stmt->fetch()['total'];

// Trips today (by drivers in this barangay)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM trips tr
    JOIN drivers d ON tr.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ? AND DATE(tr.started_at) = CURDATE()
");
$stmt->execute([$barangayId]);
$stats['trips_today'] = $stmt->fetch()['total'];

// Revenue today
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(tr.actual_fare), 0) as total FROM trips tr
    JOIN drivers d ON tr.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ? AND DATE(tr.started_at) = CURDATE() AND tr.status = 'completed'
");
$stmt->execute([$barangayId]);
$stats['revenue_today'] = $stmt->fetch()['total'];

// Pending complaints (for drivers in this barangay)
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM complaints c
    JOIN drivers d ON c.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ? AND c.status = 'pending'
");
$stmt->execute([$barangayId]);
$stats['pending_complaints'] = $stmt->fetch()['total'];

// Pending violations
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM violations v
    JOIN drivers d ON v.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ? AND v.status = 'pending'
");
$stmt->execute([$barangayId]);
$stats['pending_violations'] = $stmt->fetch()['total'];

// Drivers list
$stmt = $pdo->prepare("
    SELECT d.id, d.first_name, d.last_name, d.contact_number, d.status,
           t.name as toda_name
    FROM drivers d
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ?
    ORDER BY d.first_name
");
$stmt->execute([$barangayId]);
$stats['drivers'] = $stmt->fetchAll();

// Recent trips
$stmt = $pdo->prepare("
    SELECT tr.*, d.first_name, d.last_name, tc.plate_number
    FROM trips tr
    JOIN drivers d ON tr.driver_id = d.id
    JOIN tricycles tc ON tr.tricycle_id = tc.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ?
    ORDER BY tr.started_at DESC LIMIT 15
");
$stmt->execute([$barangayId]);
$stats['recent_trips'] = $stmt->fetchAll();

// Tricycles list
$stmt = $pdo->prepare("
    SELECT tr.id, tr.plate_number, tr.body_number, tr.color, tr.model, tr.status, tr.driver_id,
           d.first_name as driver_first, d.last_name as driver_last
    FROM tricycles tr
    LEFT JOIN drivers d ON tr.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ?
    ORDER BY tr.plate_number
");
$stmt->execute([$barangayId]);
$stats['tricycles'] = $stmt->fetchAll();

// TODAs list
$stmt = $pdo->prepare("
    SELECT id, name FROM todas WHERE barangay_id = ? AND status = 'active' ORDER BY name
");
$stmt->execute([$barangayId]);
$stats['todas'] = $stmt->fetchAll();

// Recent complaints
$stmt = $pdo->prepare("
    SELECT c.*, d.first_name, d.last_name
    FROM complaints c
    JOIN drivers d ON c.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ?
    ORDER BY c.created_at DESC LIMIT 10
");
$stmt->execute([$barangayId]);
$stats['recent_complaints'] = $stmt->fetchAll();

// Recent violations
$stmt = $pdo->prepare("
    SELECT v.*, d.first_name, d.last_name
    FROM violations v
    JOIN drivers d ON v.driver_id = d.id
    JOIN todas t ON d.toda_id = t.id
    WHERE t.barangay_id = ?
    ORDER BY v.created_at DESC LIMIT 10
");
$stmt->execute([$barangayId]);
$stats['recent_violations'] = $stmt->fetchAll();

jsonResponse($stats);
