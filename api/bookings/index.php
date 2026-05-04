<?php
/**
 * Bookings API - Fixed version
 * POST              - Create booking (passenger)
 * GET               - List bookings (filtered)
 * GET  ?id=N        - Get single booking
 * PUT  ?id=N        - Update booking status
 * GET  ?action=mine - Get passenger's active booking
 * GET  ?action=pending - Get pending bookings for drivers
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$pdo = getConnection();
$method = getMethod();
$id = getId();
$action = $_GET['action'] ?? '';

function haversine($lat1, $lng1, $lat2, $lng2) {
    $R = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat/2)*sin($dLat/2) + cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLng/2)*sin($dLng/2);
    return $R * 2 * atan2(sqrt($a), sqrt(1-$a));
}

// Helper: get driver_id from session or DB
function getDriverId($session, $pdo) {
    $driverId = $session['driver_id'] ?? null;
    if (!$driverId && ($session['role'] ?? '') === 'driver') {
        $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
        $stmt->execute([$session['user_id']]);
        $d = $stmt->fetch();
        if ($d) {
            $driverId = $d['id'];
            $_SESSION['driver_id'] = $driverId;
        }
    }
    return $driverId;
}

switch ($method) {
    case 'GET':
        $session = requireAuth();

        if ($action === 'mine') {
            $stmt = $pdo->prepare("
                SELECT b.*,
                       d.first_name as driver_first, d.last_name as driver_last,
                       d.contact_number as driver_phone,
                       tr.plate_number, tr.body_number
                FROM bookings b
                LEFT JOIN drivers d ON b.driver_id = d.id
                LEFT JOIN tricycles tr ON b.tricycle_id = tr.id
                WHERE b.passenger_id = ? AND b.status IN ('pending','accepted','in_progress')
                ORDER BY b.created_at DESC LIMIT 1
            ");
            $stmt->execute([$session['user_id']]);
            $booking = $stmt->fetch();
            jsonResponse($booking ?: ['active' => false]);

        } elseif ($action === 'pending') {
            if ($session['role'] !== 'driver' && $session['role'] !== 'admin') {
                jsonResponse(['error' => 'Driver access only'], 403);
            }

            $driverLat = $_GET['lat'] ?? null;
            $driverLng = $_GET['lng'] ?? null;

            $stmt = $pdo->prepare("
                SELECT b.*, u.first_name as passenger_first, u.last_name as passenger_last
                FROM bookings b
                JOIN users u ON b.passenger_id = u.id
                WHERE b.status = 'pending'
                ORDER BY b.created_at DESC
                LIMIT 20
            ");
            $stmt->execute();
            $bookings = $stmt->fetchAll();

            if ($driverLat && $driverLng) {
                foreach ($bookings as &$b) {
                    $b['distance_to_pickup'] = round(haversine(
                        floatval($driverLat), floatval($driverLng),
                        floatval($b['pickup_lat']), floatval($b['pickup_lng'])
                    ), 2);
                }
                unset($b);
                usort($bookings, fn($a, $b) => $a['distance_to_pickup'] <=> $b['distance_to_pickup']);
            }

            jsonResponse($bookings);

        } elseif ($action === 'driver-active') {
            $driverId = getDriverId($session, $pdo);
            if (!$driverId) {
                jsonResponse(['active' => false]);
            }
            $stmt = $pdo->prepare("
                SELECT b.*, u.first_name as passenger_first, u.last_name as passenger_last, u.phone as passenger_phone
                FROM bookings b
                JOIN users u ON b.passenger_id = u.id
                WHERE b.driver_id = ? AND b.status IN ('accepted','in_progress')
                ORDER BY b.created_at DESC LIMIT 1
            ");
            $stmt->execute([$driverId]);
            $booking = $stmt->fetch();
            jsonResponse($booking ?: ['active' => false]);

        } elseif ($id) {
            $stmt = $pdo->prepare("
                SELECT b.*,
                       u.first_name as passenger_first, u.last_name as passenger_last,
                       d.first_name as driver_first, d.last_name as driver_last,
                       tr.plate_number, tr.body_number
                FROM bookings b
                JOIN users u ON b.passenger_id = u.id
                LEFT JOIN drivers d ON b.driver_id = d.id
                LEFT JOIN tricycles tr ON b.tricycle_id = tr.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $booking = $stmt->fetch();
            if (!$booking) jsonResponse(['error' => 'Booking not found'], 404);
            jsonResponse($booking);

        } else {
            $status = $_GET['status'] ?? '';
            $limit = min((int)($_GET['limit'] ?? 50), 100);
            $sql = "SELECT b.*, u.first_name as passenger_first, u.last_name as passenger_last,
                           d.first_name as driver_first, d.last_name as driver_last
                    FROM bookings b
                    JOIN users u ON b.passenger_id = u.id
                    LEFT JOIN drivers d ON b.driver_id = d.id
                    WHERE 1=1";
            $params = [];

            // If passenger, only show their bookings
            if ($session['role'] === 'passenger') {
                $sql .= " AND b.passenger_id = ?";
                $params[] = $session['user_id'];
            }

            if ($status) { $sql .= " AND b.status = ?"; $params[] = $status; }
            $sql .= " ORDER BY b.created_at DESC LIMIT ?";
            $params[] = $limit;
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $session = requireAuth();
        $input = getInput();

        if (empty($input['pickup_lat']) || empty($input['pickup_lng']) ||
            empty($input['dropoff_lat']) || empty($input['dropoff_lng'])) {
            jsonResponse(['error' => 'Pickup and drop-off coordinates are required'], 400);
        }

        // Check for existing active booking
        $stmt = $pdo->prepare("SELECT id FROM bookings WHERE passenger_id = ? AND status IN ('pending','accepted','in_progress')");
        $stmt->execute([$session['user_id']]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'You already have an active booking'], 400);
        }

        $distance = haversine(
            floatval($input['pickup_lat']), floatval($input['pickup_lng']),
            floatval($input['dropoff_lat']), floatval($input['dropoff_lng'])
        );

        $stmt = $pdo->query("SELECT * FROM fare_rates WHERE status = 'active' ORDER BY effective_date DESC LIMIT 1");
        $rate = $stmt->fetch();
        $estimatedFare = 0;
        if ($rate) {
            $estimatedFare = calculateFare($distance, $rate['base_fare'], $rate['base_distance'], $rate['per_km_rate']);
        }

        $stmt = $pdo->prepare("
            INSERT INTO bookings (passenger_id, pickup_address, pickup_lat, pickup_lng,
                                 dropoff_address, dropoff_lat, dropoff_lng,
                                 estimated_distance, estimated_fare)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $session['user_id'],
            $input['pickup_address'] ?? 'Current Location',
            $input['pickup_lat'],
            $input['pickup_lng'],
            $input['dropoff_address'] ?? 'Destination',
            $input['dropoff_lat'],
            $input['dropoff_lng'],
            round($distance, 2),
            round($estimatedFare, 2)
        ]);

        jsonResponse([
            'message' => 'Booking created',
            'booking_id' => $pdo->lastInsertId(),
            'estimated_distance' => round($distance, 2),
            'estimated_fare' => round($estimatedFare, 2)
        ], 201);
        break;

    case 'PUT':
        $session = requireAuth();
        if (!$id) jsonResponse(['error' => 'Booking ID required'], 400);
        $input = getInput();
        $newStatus = $input['status'] ?? '';

        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ?");
        $stmt->execute([$id]);
        $booking = $stmt->fetch();
        if (!$booking) jsonResponse(['error' => 'Booking not found'], 404);

        if ($newStatus === 'accepted') {
            // ── Driver accepts ──
            $driverId = getDriverId($session, $pdo);
            if (!$driverId) {
                jsonResponse(['error' => 'No driver record found. Please contact admin to set up your driver profile.'], 403);
            }
            if ($booking['status'] !== 'pending') {
                jsonResponse(['error' => 'Booking is no longer available'], 400);
            }

            // Check if driver has a tricycle
            $stmt = $pdo->prepare("SELECT id FROM tricycles WHERE driver_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$driverId]);
            $tricycle = $stmt->fetch();

            if (!$tricycle) {
                jsonResponse(['error' => 'You have no active tricycle assigned. Please contact admin to assign a tricycle to your account.'], 400);
            }

            // Atomic accept with WHERE status='pending' to prevent race condition
            $stmt = $pdo->prepare("
                UPDATE bookings SET status = 'accepted', driver_id = ?, tricycle_id = ?, accepted_at = NOW()
                WHERE id = ? AND status = 'pending'
            ");
            $stmt->execute([$driverId, $tricycle['id'], $id]);

            if ($stmt->rowCount() === 0) {
                jsonResponse(['error' => 'Booking was already taken by another driver'], 400);
            }

            jsonResponse(['message' => 'Booking accepted']);

        } elseif ($newStatus === 'in_progress') {
            // ── Driver starts trip ──
            $driverId = getDriverId($session, $pdo);
            if (!$driverId) {
                jsonResponse(['error' => 'Driver record not found'], 403);
            }
            if ($booking['status'] !== 'accepted') {
                jsonResponse(['error' => 'Booking must be accepted first'], 400);
            }
            // Verify this driver owns the booking
            if ($booking['driver_id'] != $driverId) {
                jsonResponse(['error' => 'This booking belongs to another driver'], 403);
            }

            $stmt = $pdo->prepare("UPDATE bookings SET status = 'in_progress', started_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);

            // Get tricycle
            $tricycleId = $booking['tricycle_id'];
            if (!$tricycleId) {
                $stmt = $pdo->prepare("SELECT id FROM tricycles WHERE driver_id = ? AND status = 'active' LIMIT 1");
                $stmt->execute([$driverId]);
                $tc = $stmt->fetch();
                $tricycleId = $tc ? $tc['id'] : null;
            }

            $tripId = null;
            if ($tricycleId) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO trips (tricycle_id, driver_id, origin, destination, distance_km,
                                           computed_fare, passenger_count, status,
                                           start_lat, start_lng, current_lat, current_lng)
                        VALUES (?, ?, ?, ?, 0, 0, 1, 'active', ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $tricycleId, $driverId,
                        $booking['pickup_address'], $booking['dropoff_address'],
                        $booking['pickup_lat'], $booking['pickup_lng'],
                        $booking['pickup_lat'], $booking['pickup_lng']
                    ]);
                    $tripId = $pdo->lastInsertId();
                    $stmt = $pdo->prepare("UPDATE bookings SET trip_id = ? WHERE id = ?");
                    $stmt->execute([$tripId, $id]);
                } catch (Exception $e) {
                    // Trip creation failed but booking is in_progress
                }
            }

            jsonResponse(['message' => 'Trip started', 'trip_id' => $tripId]);

        } elseif ($newStatus === 'completed') {
            // ── Complete booking ──
            if ($booking['status'] !== 'in_progress') {
                jsonResponse(['error' => 'Booking must be in progress to complete'], 400);
            }
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['message' => 'Booking completed']);

        } elseif ($newStatus === 'cancelled') {
            // ── Cancel booking - verify ownership ──
            if ($booking['status'] === 'completed') {
                jsonResponse(['error' => 'Cannot cancel a completed booking'], 400);
            }

            $cancelledBy = 'system';
            if ($session['role'] === 'passenger' && $booking['passenger_id'] == $session['user_id']) {
                $cancelledBy = 'passenger';
            } elseif ($session['role'] === 'driver') {
                $driverId = getDriverId($session, $pdo);
                if ($booking['driver_id'] == $driverId) $cancelledBy = 'driver';
            } elseif ($session['role'] === 'admin') {
                $cancelledBy = 'system';
            }

            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled', cancelled_by = ? WHERE id = ?");
            $stmt->execute([$cancelledBy, $id]);
            jsonResponse(['message' => 'Booking cancelled']);

        } else {
            jsonResponse(['error' => 'Invalid status'], 400);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
