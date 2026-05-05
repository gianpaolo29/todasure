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
            // Get single driver
            $stmt = $pdo->prepare("
                SELECT d.*, u.username, u.email, u.status as account_status,
                       t.name as toda_name, b.id as barangay_id, b.name as barangay_name
                FROM drivers d
                JOIN users u ON d.user_id = u.id
                LEFT JOIN todas t ON d.toda_id = t.id
                LEFT JOIN barangays b ON t.barangay_id = b.id
                WHERE d.id = ?
            ");
            $stmt->execute([$id]);
            $driver = $stmt->fetch();
            if (!$driver) {
                jsonResponse(['error' => 'Driver not found'], 404);
            }
            jsonResponse($driver);
        } else {
            // List all drivers
            $search = $_GET['search'] ?? '';
            $toda_id = $_GET['toda_id'] ?? '';
            $status = $_GET['status'] ?? '';

            $sql = "SELECT d.*, u.username, t.name as toda_name, b.name as barangay_name
                    FROM drivers d
                    JOIN users u ON d.user_id = u.id
                    LEFT JOIN todas t ON d.toda_id = t.id
                    LEFT JOIN barangays b ON t.barangay_id = b.id
                    WHERE 1=1";
            $params = [];

            if ($search) {
                $sql .= " AND (d.first_name LIKE ? OR d.last_name LIKE ? OR d.license_number LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            if ($toda_id) {
                $sql .= " AND d.toda_id = ?";
                $params[] = $toda_id;
            }
            if ($status) {
                $sql .= " AND d.status = ?";
                $params[] = $status;
            }

            $sql .= " ORDER BY d.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        $session = requireAuth();
        // Allow admin and barangay officials to add drivers
        if ($session['role'] !== 'admin' && $session['role'] !== 'barangay') {
            jsonResponse(['error' => 'Not authorized'], 403);
        }
        $input = getInput();

        // Promote existing user to driver
        $userId = $input['user_id'] ?? null;
        if (!$userId) {
            jsonResponse(['error' => 'User ID is required. Select a user to promote to driver.'], 400);
        }

        // Check user exists
        $stmt = $pdo->prepare("SELECT id, first_name, last_name, phone, role FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        if (!$user) {
            jsonResponse(['error' => 'User not found'], 404);
        }

        // Check if already a driver
        $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            jsonResponse(['error' => 'This user is already registered as a driver'], 409);
        }

        $firstName = $input['first_name'] ?? $user['first_name'];
        $lastName = $input['last_name'] ?? $user['last_name'] ?? '';

        // Auto-assign TODA based on barangay
        $todaId = $input['toda_id'] ?? null;
        if (!$todaId) {
            // Use barangay_id from input (admin) or session (barangay official)
            $brgyId = $input['barangay_id'] ?? $session['barangay_id'] ?? null;
            if ($brgyId) {
                $stmt = $pdo->prepare("SELECT id FROM todas WHERE barangay_id = ? AND status = 'active' LIMIT 1");
                $stmt->execute([$brgyId]);
                $toda = $stmt->fetch();
                if ($toda) {
                    $todaId = $toda['id'];
                } else {
                    // Auto-create a TODA for this barangay
                    $stmt = $pdo->prepare("SELECT name FROM barangays WHERE id = ?");
                    $stmt->execute([$brgyId]);
                    $brgyName = $stmt->fetch()['name'] ?? 'Unknown';
                    $stmt = $pdo->prepare("INSERT INTO todas (name, barangay_id) VALUES (?, ?)");
                    $stmt->execute([$brgyName . ' TODA', $brgyId]);
                    $todaId = $pdo->lastInsertId();
                }
            } else {
                // Admin with no barangay — use first active TODA
                $stmt = $pdo->query("SELECT id FROM todas WHERE status = 'active' LIMIT 1");
                $toda = $stmt->fetch();
                if ($toda) $todaId = $toda['id'];
            }
        }

        $pdo->beginTransaction();
        try {
            // Update user role to driver
            $stmt = $pdo->prepare("UPDATE users SET role = 'driver' WHERE id = ?");
            $stmt->execute([$userId]);

            // Create driver record
            $stmt = $pdo->prepare("
                INSERT INTO drivers (user_id, toda_id, first_name, last_name, middle_name, contact_number, address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $todaId,
                $firstName,
                $lastName,
                $input['middle_name'] ?? null,
                $input['contact_number'] ?? $user['phone'] ?? null,
                $input['address'] ?? null
            ]);
            $driverId = $pdo->lastInsertId();

            $pdo->commit();
            jsonResponse(['message' => 'Driver added successfully', 'id' => $driverId], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Failed to add driver: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        $session = requireAuth();
        if (!$id) jsonResponse(['error' => 'Driver ID required'], 400);

        // Allow drivers to update their own record, admin and barangay can update any
        if ($session['role'] !== 'admin' && $session['role'] !== 'barangay') {
            $stmt = $pdo->prepare("SELECT id FROM drivers WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $session['user_id']]);
            if (!$stmt->fetch()) {
                jsonResponse(['error' => 'Not authorized'], 403);
            }
        }

        $input = getInput();
        $fields = [];
        $params = [];

        // If barangay_id is provided, resolve it to toda_id
        if (isset($input['barangay_id']) && $input['barangay_id']) {
            $brgyId = $input['barangay_id'];
            $stmt = $pdo->prepare("SELECT id FROM todas WHERE barangay_id = ? AND status = 'active' LIMIT 1");
            $stmt->execute([$brgyId]);
            $toda = $stmt->fetch();
            if ($toda) {
                $input['toda_id'] = $toda['id'];
            } else {
                $stmt = $pdo->prepare("SELECT name FROM barangays WHERE id = ?");
                $stmt->execute([$brgyId]);
                $brgyName = $stmt->fetch()['name'] ?? 'Unknown';
                $stmt = $pdo->prepare("INSERT INTO todas (name, barangay_id) VALUES (?, ?)");
                $stmt->execute([$brgyName . ' TODA', $brgyId]);
                $input['toda_id'] = $pdo->lastInsertId();
            }
        }

        $allowed = ['first_name', 'last_name', 'middle_name', 'contact_number', 'address', 'toda_id', 'status'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }

        if (empty($fields)) {
            jsonResponse(['error' => 'No fields to update'], 400);
        }

        $params[] = $id;
        $sql = "UPDATE drivers SET " . implode(', ', $fields) . " WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        jsonResponse(['message' => 'Driver updated successfully']);
        break;

    case 'DELETE':
        $session = requireAuth();
        if ($session['role'] !== 'admin' && $session['role'] !== 'barangay') {
            jsonResponse(['error' => 'Not authorized'], 403);
        }
        if (!$id) jsonResponse(['error' => 'Driver ID required'], 400);

        // Get user_id before deleting
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE id = ?");
        $stmt->execute([$id]);
        $driver = $stmt->fetch();

        if (!$driver) jsonResponse(['error' => 'Driver not found'], 404);

        $pdo->beginTransaction();
        try {
            // Delete driver record
            $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
            $stmt->execute([$id]);

            // Revert user role back to passenger
            $stmt = $pdo->prepare("UPDATE users SET role = 'passenger' WHERE id = ?");
            $stmt->execute([$driver['user_id']]);

            $pdo->commit();
            jsonResponse(['message' => 'Driver deleted successfully']);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Failed to delete driver: ' . $e->getMessage()], 500);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
