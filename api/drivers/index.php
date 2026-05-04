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
                SELECT d.*, u.username, u.status as account_status,
                       t.name as toda_name, b.name as barangay_name
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
        requireAdmin();
        $input = getInput();

        // Validate required fields
        $required = ['first_name', 'last_name', 'password'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        // Email or username required
        $email = $input['email'] ?? '';
        $username = $input['username'] ?? '';
        if (empty($email) && empty($username)) {
            jsonResponse(['error' => 'Email is required'], 400);
        }
        if (empty($username)) $username = explode('@', $email)[0];
        if (empty($email)) $email = $username . '@todasure.local';

        // Check duplicates
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
        $stmt->execute([$email, $username]);
        if ($stmt->fetch()) {
            // Try with random suffix
            $username = $username . rand(100, 999);
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                jsonResponse(['error' => 'Email already registered'], 409);
            }
        }

        $pdo->beginTransaction();
        try {
            // Create user account
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, first_name, last_name, phone, role) VALUES (?, ?, ?, ?, ?, ?, 'driver')");
            $stmt->execute([$username, $email, $hashedPassword, $input['first_name'], $input['last_name'], $input['contact_number'] ?? null]);
            $userId = $pdo->lastInsertId();

            // Create driver record
            $stmt = $pdo->prepare("
                INSERT INTO drivers (user_id, toda_id, first_name, last_name, middle_name, contact_number, address)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $input['toda_id'] ?? null,
                $input['first_name'],
                $input['last_name'],
                $input['middle_name'] ?? null,
                $input['contact_number'] ?? null,
                $input['address'] ?? null
            ]);
            $driverId = $pdo->lastInsertId();

            $pdo->commit();
            jsonResponse(['message' => 'Driver registered successfully', 'id' => $driverId], 201);
        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['error' => 'Registration failed: ' . $e->getMessage()], 500);
        }
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Driver ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];

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
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Driver ID required'], 400);

        $stmt = $pdo->prepare("UPDATE drivers SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['message' => 'Driver deactivated successfully']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
