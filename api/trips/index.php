<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$pdo = getConnection();
$method = getMethod();
$id = getId();

switch ($method) {
    case 'GET':
        if ($id) {
            $stmt = $pdo->prepare("
                SELECT t.*, d.first_name, d.last_name,
                       tr.plate_number, tr.body_number
                FROM trips t
                JOIN drivers d ON t.driver_id = d.id
                JOIN tricycles tr ON t.tricycle_id = tr.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $trip = $stmt->fetch();
            if (!$trip) jsonResponse(['error' => 'Trip not found'], 404);
            jsonResponse($trip);
        } else {
            $driver_id = $_GET['driver_id'] ?? '';
            $status = $_GET['status'] ?? '';
            $date_from = $_GET['date_from'] ?? '';
            $date_to = $_GET['date_to'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $offset = (int)($_GET['offset'] ?? 0);

            $sql = "SELECT t.*, d.first_name, d.last_name, tr.plate_number, tr.body_number
                    FROM trips t
                    JOIN drivers d ON t.driver_id = d.id
                    JOIN tricycles tr ON t.tricycle_id = tr.id
                    WHERE 1=1";
            $params = [];

            if ($driver_id) {
                $sql .= " AND t.driver_id = ?";
                $params[] = $driver_id;
            }
            if ($status) {
                $sql .= " AND t.status = ?";
                $params[] = $status;
            }
            if ($date_from) {
                $sql .= " AND t.started_at >= ?";
                $params[] = $date_from;
            }
            if ($date_to) {
                $sql .= " AND t.started_at <= ?";
                $params[] = $date_to . ' 23:59:59';
            }

            $sql .= " ORDER BY t.started_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        // Start a new trip - called by driver from web dashboard or admin
        $input = getInput();

        // Driver can start their own trip from dashboard
        session_start();
        if (isset($_SESSION['driver_id'])) {
            $driverId = $_SESSION['driver_id'];
        } elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($input['driver_id'])) {
            $driverId = $input['driver_id'];
        } else {
            jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $required = ['tricycle_id', 'origin', 'destination', 'distance_km'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        // Get active fare rate for the driver's TODA barangay
        $stmt = $pdo->prepare("
            SELECT fr.* FROM fare_rates fr
            JOIN todas t ON fr.barangay_id = t.barangay_id
            JOIN drivers d ON d.toda_id = t.id
            WHERE d.id = ? AND fr.status = 'active'
            ORDER BY fr.effective_date DESC LIMIT 1
        ");
        $stmt->execute([$driverId]);
        $rate = $stmt->fetch();

        // Calculate fare based on distance
        $distanceKm = floatval($input['distance_km']);
        $computedFare = 0;
        $fareRateId = null;

        if ($rate) {
            $fareRateId = $rate['id'];
            $computedFare = calculateFare($distanceKm, $rate['base_fare'], $rate['base_distance'], $rate['per_km_rate']);
        }

        $actualFare = isset($input['actual_fare']) ? floatval($input['actual_fare']) : $computedFare;
        $passengerCount = $input['passenger_count'] ?? 1;

        $stmt = $pdo->prepare("
            INSERT INTO trips (tricycle_id, driver_id, fare_rate_id, origin, destination,
                             distance_km, computed_fare, actual_fare, passenger_count, status, ended_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())
        ");
        $stmt->execute([
            $input['tricycle_id'],
            $driverId,
            $fareRateId,
            $input['origin'],
            $input['destination'],
            round($distanceKm, 2),
            round($computedFare, 2),
            round($actualFare, 2),
            $passengerCount
        ]);

        $tripId = $pdo->lastInsertId();

        // Check for fare violation (overcharge > 20%)
        if ($actualFare > $computedFare * 1.20 && $computedFare > 0) {
            $stmt = $pdo->prepare("
                INSERT INTO violations (driver_id, trip_id, violation_type, description, severity)
                VALUES (?, ?, 'fare_overcharge', ?, 'moderate')
            ");
            $stmt->execute([
                $driverId,
                $tripId,
                "Overcharged: PHP " . round($actualFare, 2) . " vs computed PHP " . round($computedFare, 2)
            ]);
        }

        jsonResponse([
            'message' => 'Trip recorded',
            'trip_id' => $tripId,
            'distance_km' => round($distanceKm, 2),
            'computed_fare' => round($computedFare, 2),
            'actual_fare' => round($actualFare, 2)
        ], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Trip ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];

        $allowed = ['status', 'actual_fare', 'passenger_count'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE trips SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);

        jsonResponse(['message' => 'Trip updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
