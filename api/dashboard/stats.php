<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
requireAdmin();

$pdo = getConnection();

// Total counts
$stats = [];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM drivers WHERE status = 'active'");
$stats['total_drivers'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM tricycles WHERE status = 'active'");
$stats['total_tricycles'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM todas WHERE status = 'active'");
$stats['total_todas'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM trips WHERE DATE(started_at) = CURDATE()");
$stats['trips_today'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COALESCE(SUM(actual_fare), 0) as total FROM trips WHERE DATE(started_at) = CURDATE() AND status = 'completed'");
$stats['revenue_today'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM trips WHERE status = 'active'");
$stats['active_trips'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM complaints WHERE status = 'pending'");
$stats['pending_complaints'] = $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM violations WHERE status = 'pending'");
$stats['pending_violations'] = $stmt->fetch()['total'];

// Recent trips (last 10)
$stmt = $pdo->query("
    SELECT t.*, d.first_name, d.last_name, tr.plate_number
    FROM trips t
    JOIN drivers d ON t.driver_id = d.id
    JOIN tricycles tr ON t.tricycle_id = tr.id
    ORDER BY t.started_at DESC LIMIT 10
");
$stats['recent_trips'] = $stmt->fetchAll();

// Monthly revenue (last 6 months)
$stmt = $pdo->query("
    SELECT DATE_FORMAT(started_at, '%Y-%m') as month,
           COUNT(*) as trip_count,
           COALESCE(SUM(actual_fare), 0) as revenue
    FROM trips
    WHERE status = 'completed' AND started_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(started_at, '%Y-%m')
    ORDER BY month
");
$stats['monthly_revenue'] = $stmt->fetchAll();

jsonResponse($stats);
