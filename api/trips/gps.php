<?php
/**
 * GPS Trip Management API
 * Handles real-time GPS-based trip tracking:
 *   POST ?action=start    - Start a new GPS-tracked trip
 *   POST ?action=update   - Update GPS location during trip
 *   POST ?action=end      - End trip and finalize fare
 *   GET  ?action=active   - Get all active trips (for admin map)
 *   GET  ?action=status   - Get current trip status for driver
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$pdo = getConnection();
$method = getMethod();
$action = $_GET['action'] ?? '';

// Haversine formula: calculate distance between two GPS points in km
function haversineDistance($lat1, $lng1, $lat2, $lng2) {
    $R = 6371; // Earth radius in km
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

switch ($method) {
    case 'POST':
        $input = getInput();

        if ($action === 'start') {
            // ── Start a new GPS trip ──
            session_start();
            if (!isset($_SESSION['driver_id'])) {
                jsonResponse(['error' => 'Unauthorized - driver login required'], 401);
            }
            $driverId = $_SESSION['driver_id'];

            // Check for existing active trip
            $stmt = $pdo->prepare("SELECT id FROM trips WHERE driver_id = ? AND status = 'active'");
            $stmt->execute([$driverId]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'You already have an active trip. End it first.'], 400);
            }

            // Required fields
            if (empty($input['tricycle_id'])) {
                jsonResponse(['error' => 'Tricycle ID is required'], 400);
            }
            $lat = floatval($input['latitude'] ?? 0);
            $lng = floatval($input['longitude'] ?? 0);

            if ($lat == 0 || $lng == 0) {
                jsonResponse(['error' => 'GPS coordinates are required'], 400);
            }

            // Get active fare rate (via driver's TODA → barangay)
            $stmt = $pdo->prepare("
                SELECT fr.* FROM fare_rates fr
                JOIN todas t ON fr.barangay_id = t.barangay_id
                JOIN drivers d ON d.toda_id = t.id
                WHERE d.id = ? AND fr.status = 'active'
                ORDER BY fr.effective_date DESC LIMIT 1
            ");
            $stmt->execute([$driverId]);
            $rate = $stmt->fetch();

            // Fallback: use any active fare rate if driver has no TODA
            if (!$rate) {
                $stmt = $pdo->query("SELECT * FROM fare_rates WHERE status = 'active' ORDER BY effective_date DESC LIMIT 1");
                $rate = $stmt->fetch();
            }
            $fareRateId = $rate ? $rate['id'] : null;

            // Create active trip
            $stmt = $pdo->prepare("
                INSERT INTO trips (tricycle_id, driver_id, fare_rate_id, origin, destination,
                                 distance_km, computed_fare, actual_fare, passenger_count,
                                 status, start_lat, start_lng, current_lat, current_lng, started_at)
                VALUES (?, ?, ?, ?, '', 0, 0, 0, ?, 'active', ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $input['tricycle_id'],
                $driverId,
                $fareRateId,
                $input['origin'] ?? 'GPS Start',
                $input['passenger_count'] ?? 1,
                $lat, $lng, $lat, $lng
            ]);

            $tripId = $pdo->lastInsertId();

            // Record first GPS breadcrumb
            $stmt = $pdo->prepare("
                INSERT INTO trip_locations (trip_id, latitude, longitude, distance_from_prev)
                VALUES (?, ?, ?, 0)
            ");
            $stmt->execute([$tripId, $lat, $lng]);

            jsonResponse([
                'message' => 'Trip started',
                'trip_id' => $tripId,
                'fare_rate' => $rate ? [
                    'base_fare' => floatval($rate['base_fare']),
                    'base_distance' => floatval($rate['base_distance']),
                    'per_km_rate' => floatval($rate['per_km_rate']),
                    'discount_senior' => floatval($rate['discount_senior'])
                ] : null
            ], 201);

        } elseif ($action === 'update') {
            // ── Update GPS location during trip ──
            session_start();
            $driverId = $_SESSION['driver_id'] ?? null;
            if (!$driverId) {
                // Lookup from DB
                $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 0]);
                $d = $stmt->fetch();
                if ($d) { $driverId = $d['id']; $_SESSION['driver_id'] = $driverId; }
                else { jsonResponse(['error' => 'Driver not found'], 401); }
            }

            $lat = floatval($input['latitude'] ?? 0);
            $lng = floatval($input['longitude'] ?? 0);

            if ($lat == 0 || $lng == 0) {
                jsonResponse(['error' => 'GPS coordinates required'], 400);
            }

            // Get active trip
            $stmt = $pdo->prepare("
                SELECT t.*, fr.base_fare, fr.base_distance, fr.per_km_rate
                FROM trips t
                LEFT JOIN fare_rates fr ON t.fare_rate_id = fr.id
                WHERE t.driver_id = ? AND t.status = 'active'
            ");
            $stmt->execute([$driverId]);
            $trip = $stmt->fetch();

            if (!$trip) {
                jsonResponse(['error' => 'No active trip found'], 404);
            }

            // Get last recorded location
            $stmt = $pdo->prepare("
                SELECT latitude, longitude FROM trip_locations
                WHERE trip_id = ? ORDER BY id DESC LIMIT 1
            ");
            $stmt->execute([$trip['id']]);
            $lastLoc = $stmt->fetch();

            // Calculate distance from last point
            $segmentDistance = 0;
            if ($lastLoc) {
                $segmentDistance = haversineDistance(
                    $lastLoc['latitude'], $lastLoc['longitude'],
                    $lat, $lng
                );
            }

            // Filter out GPS jitter (ignore movements less than 5 meters)
            if ($segmentDistance < 0.005) {
                $segmentDistance = 0;
            }

            // Record breadcrumb
            $stmt = $pdo->prepare("
                INSERT INTO trip_locations (trip_id, latitude, longitude, distance_from_prev)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$trip['id'], $lat, $lng, round($segmentDistance, 4)]);

            // Update total distance and current position
            $newDistance = floatval($trip['distance_km']) + $segmentDistance;
            $computedFare = 0;
            if ($trip['base_fare']) {
                $computedFare = calculateFare(
                    $newDistance,
                    $trip['base_fare'],
                    $trip['base_distance'],
                    $trip['per_km_rate']
                );
            }

            $stmt = $pdo->prepare("
                UPDATE trips SET
                    distance_km = ?,
                    computed_fare = ?,
                    current_lat = ?,
                    current_lng = ?
                WHERE id = ?
            ");
            $stmt->execute([
                round($newDistance, 2),
                round($computedFare, 2),
                $lat, $lng,
                $trip['id']
            ]);

            jsonResponse([
                'trip_id' => $trip['id'],
                'distance_km' => round($newDistance, 2),
                'computed_fare' => round($computedFare, 2),
                'segment_distance' => round($segmentDistance, 4),
                'latitude' => $lat,
                'longitude' => $lng
            ]);

        } elseif ($action === 'end') {
            // ── End trip and finalize ──
            session_start();
            $driverId = $_SESSION['driver_id'] ?? null;
            if (!$driverId) {
                $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 0]);
                $d = $stmt->fetch();
                if ($d) { $driverId = $d['id']; $_SESSION['driver_id'] = $driverId; }
                else { jsonResponse(['error' => 'Driver not found'], 401); }
            }

            // Get active trip
            $stmt = $pdo->prepare("
                SELECT t.*, fr.base_fare, fr.base_distance, fr.per_km_rate
                FROM trips t
                LEFT JOIN fare_rates fr ON t.fare_rate_id = fr.id
                WHERE t.driver_id = ? AND t.status = 'active'
            ");
            $stmt->execute([$driverId]);
            $trip = $stmt->fetch();

            if (!$trip) {
                jsonResponse(['error' => 'No active trip found'], 404);
            }

            $lat = floatval($input['latitude'] ?? $trip['current_lat']);
            $lng = floatval($input['longitude'] ?? $trip['current_lng']);
            $destination = $input['destination'] ?? 'GPS End';
            $actualFare = isset($input['actual_fare']) ? floatval($input['actual_fare']) : floatval($trip['computed_fare']);

            // Final distance calculation
            $stmt = $pdo->prepare("
                SELECT SUM(distance_from_prev) as total_distance FROM trip_locations WHERE trip_id = ?
            ");
            $stmt->execute([$trip['id']]);
            $totalDist = $stmt->fetch();
            $finalDistance = floatval($totalDist['total_distance'] ?? $trip['distance_km']);

            // Recalculate final fare
            $computedFare = 0;
            $baseFare = $trip['base_fare'];
            $baseDist = $trip['base_distance'];
            $perKm = $trip['per_km_rate'];

            // Fallback if no fare rate linked to trip
            if (!$baseFare) {
                $stmt = $pdo->query("SELECT * FROM fare_rates WHERE status = 'active' ORDER BY effective_date DESC LIMIT 1");
                $fallbackRate = $stmt->fetch();
                if ($fallbackRate) {
                    $baseFare = $fallbackRate['base_fare'];
                    $baseDist = $fallbackRate['base_distance'];
                    $perKm = $fallbackRate['per_km_rate'];
                }
            }

            if ($baseFare) {
                $computedFare = calculateFare($finalDistance, $baseFare, $baseDist, $perKm);
            }

            if ($actualFare == 0) $actualFare = $computedFare;

            // Finalize trip
            $stmt = $pdo->prepare("
                UPDATE trips SET
                    status = 'completed',
                    destination = ?,
                    distance_km = ?,
                    computed_fare = ?,
                    actual_fare = ?,
                    end_lat = ?,
                    end_lng = ?,
                    current_lat = NULL,
                    current_lng = NULL,
                    ended_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $destination,
                round($finalDistance, 2),
                round($computedFare, 2),
                round($actualFare, 2),
                $lat, $lng,
                $trip['id']
            ]);

            // Check for overcharging violation
            if ($actualFare > $computedFare * 1.20 && $computedFare > 0) {
                $stmt = $pdo->prepare("
                    INSERT INTO violations (driver_id, trip_id, violation_type, description, severity)
                    VALUES (?, ?, 'fare_overcharge', ?, 'moderate')
                ");
                $stmt->execute([
                    $driverId,
                    $trip['id'],
                    "Overcharged: PHP " . round($actualFare, 2) . " vs computed PHP " . round($computedFare, 2)
                ]);

                // Notify admin of violation
                try {
                    $pdo->prepare("INSERT INTO notifications (role_target, type, title, message, link) VALUES ('admin', 'violation', 'Fare Overcharge Detected', ?, 'violations.html')")
                        ->execute(["PHP " . round($actualFare, 2) . " charged vs PHP " . round($computedFare, 2) . " computed"]);
                } catch(Exception $e) {}
            }

            jsonResponse([
                'message' => 'Trip completed',
                'trip_id' => $trip['id'],
                'distance_km' => round($finalDistance, 2),
                'computed_fare' => round($computedFare, 2),
                'actual_fare' => round($actualFare, 2),
                'overcharge' => ($actualFare > $computedFare * 1.20 && $computedFare > 0)
            ]);

        } else {
            jsonResponse(['error' => 'Invalid action. Use: start, update, end'], 400);
        }
        break;

    case 'GET':
        if ($action === 'active') {
            // ── Get all active trips (for admin map view) ──
            requireAuth();

            $stmt = $pdo->prepare("
                SELECT t.id, t.origin, t.distance_km, t.computed_fare, t.passenger_count,
                       t.current_lat, t.current_lng, t.start_lat, t.start_lng, t.started_at,
                       d.first_name, d.last_name,
                       tr.plate_number, tr.body_number
                FROM trips t
                JOIN drivers d ON t.driver_id = d.id
                LEFT JOIN tricycles tr ON t.tricycle_id = tr.id
                WHERE t.status = 'active'
                ORDER BY t.started_at DESC
            ");
            $stmt->execute();
            jsonResponse($stmt->fetchAll());

        } elseif ($action === 'status') {
            // ── Get driver's current trip status ──
            session_start();
            $driverId = $_SESSION['driver_id'] ?? null;
            if (!$driverId) {
                $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
                $stmt->execute([$_SESSION['user_id'] ?? 0]);
                $d = $stmt->fetch();
                if ($d) { $driverId = $d['id']; $_SESSION['driver_id'] = $driverId; }
                else { jsonResponse(['active' => false]); }
            }

            $stmt = $pdo->prepare("
                SELECT t.*, fr.base_fare, fr.base_distance, fr.per_km_rate, fr.discount_senior
                FROM trips t
                LEFT JOIN fare_rates fr ON t.fare_rate_id = fr.id
                WHERE t.driver_id = ? AND t.status = 'active'
            ");
            $stmt->execute([$driverId]);
            $trip = $stmt->fetch();

            if ($trip) {
                jsonResponse(['active' => true, 'trip' => $trip]);
            } else {
                jsonResponse(['active' => false]);
            }

        } elseif ($action === 'route') {
            // ── Get trip route breadcrumbs ──
            requireAuth();
            $tripId = $_GET['trip_id'] ?? null;
            if (!$tripId) jsonResponse(['error' => 'trip_id required'], 400);

            $stmt = $pdo->prepare("
                SELECT latitude, longitude, distance_from_prev, recorded_at
                FROM trip_locations WHERE trip_id = ? ORDER BY id ASC
            ");
            $stmt->execute([$tripId]);
            jsonResponse($stmt->fetchAll());

        } else {
            jsonResponse(['error' => 'Invalid action. Use: active, status, route'], 400);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
