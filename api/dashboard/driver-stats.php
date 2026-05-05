<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$session = requireAuth();

if ($session['role'] !== 'driver' || !isset($session['driver_id'])) {
    jsonResponse(['error' => 'Driver access only'], 403);
}

$driverId = $session['driver_id'];
$pdo = getConnection();
$stats = [];

// Driver info
$stmt = $pdo->prepare("SELECT id, first_name, last_name, contact_number, address, toda_id FROM drivers WHERE id = ?");
$stmt->execute([$driverId]);
$stats['driver'] = $stmt->fetch();

// Today's stats
$stmt = $pdo->prepare("
    SELECT COUNT(*) as trip_count, COALESCE(SUM(actual_fare), 0) as earnings
    FROM trips WHERE driver_id = ? AND DATE(started_at) = CURDATE() AND status = 'completed'
");
$stmt->execute([$driverId]);
$stats['today'] = $stmt->fetch();

// This week
$stmt = $pdo->prepare("
    SELECT COUNT(*) as trip_count, COALESCE(SUM(actual_fare), 0) as earnings
    FROM trips WHERE driver_id = ? AND YEARWEEK(started_at) = YEARWEEK(CURDATE()) AND status = 'completed'
");
$stmt->execute([$driverId]);
$stats['this_week'] = $stmt->fetch();

// This month
$stmt = $pdo->prepare("
    SELECT COUNT(*) as trip_count, COALESCE(SUM(actual_fare), 0) as earnings
    FROM trips WHERE driver_id = ? AND MONTH(started_at) = MONTH(CURDATE()) AND YEAR(started_at) = YEAR(CURDATE()) AND status = 'completed'
");
$stmt->execute([$driverId]);
$stats['this_month'] = $stmt->fetch();

// Violation count
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM violations WHERE driver_id = ? AND status IN ('pending', 'confirmed')");
$stmt->execute([$driverId]);
$stats['violations'] = $stmt->fetch()['total'];

// Recent trips
$stmt = $pdo->prepare("
    SELECT t.*, tr.plate_number, tr.body_number
    FROM trips t
    JOIN tricycles tr ON t.tricycle_id = tr.id
    WHERE t.driver_id = ?
    ORDER BY t.started_at DESC LIMIT 10
");
$stmt->execute([$driverId]);
$stats['recent_trips'] = $stmt->fetchAll();

// Daily earnings (last 7 days)
$stmt = $pdo->prepare("
    SELECT DATE(started_at) as date, COUNT(*) as trip_count, COALESCE(SUM(actual_fare), 0) as earnings
    FROM trips
    WHERE driver_id = ? AND status = 'completed' AND started_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DATE(started_at) ORDER BY date
");
$stmt->execute([$driverId]);
$stats['daily_earnings'] = $stmt->fetchAll();

jsonResponse($stats);
