<?php
/**
 * Users Management API (Admin only)
 * GET            - List users (filterable by role)
 * POST           - Create user with specific role
 * PUT ?id=N      - Update user (role, barangay_id, status)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$pdo = getConnection();
$method = getMethod();
$id = getId();

switch ($method) {
    case 'GET':
        requireAdmin();

        if ($id) {
            $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, role, barangay_id, status, created_at FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $user = $stmt->fetch();
            if (!$user) jsonResponse(['error' => 'User not found'], 404);
            if ($user['barangay_id']) {
                $stmt2 = $pdo->prepare("SELECT name FROM barangays WHERE id = ?");
                $stmt2->execute([$user['barangay_id']]);
                $b = $stmt2->fetch();
                $user['barangay_name'] = $b ? $b['name'] : null;
            }
            jsonResponse($user);
        }

        $role = $_GET['role'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "SELECT id, username, email, first_name, last_name, phone, role, barangay_id, status, created_at
                FROM users WHERE 1=1";
        $params = [];

        if ($role) { $sql .= " AND role = ?"; $params[] = $role; }
        if ($search) {
            $sql .= " AND (first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
            $s = "%$search%";
            $params[] = $s; $params[] = $s; $params[] = $s;
        }
        $sql .= " ORDER BY created_at DESC LIMIT 100";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $users = $stmt->fetchAll();

        // Attach barangay name
        foreach ($users as &$u) {
            if ($u['barangay_id']) {
                $stmt2 = $pdo->prepare("SELECT name FROM barangays WHERE id = ?");
                $stmt2->execute([$u['barangay_id']]);
                $b = $stmt2->fetch();
                $u['barangay_name'] = $b ? $b['name'] : null;
            } else {
                $u['barangay_name'] = null;
            }
        }
        unset($u);

        jsonResponse($users);
        break;

    case 'POST':
        requireAdmin();
        $input = getInput();

        $required = ['first_name', 'last_name', 'email', 'password', 'role'];
        foreach ($required as $f) {
            if (empty($input[$f])) jsonResponse(['error' => "$f is required"], 400);
        }

        if (!in_array($input['role'], ['admin', 'driver', 'passenger', 'barangay'])) {
            jsonResponse(['error' => 'Invalid role'], 400);
        }

        // Check email
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$input['email']]);
        if ($stmt->fetch()) jsonResponse(['error' => 'Email already exists'], 409);

        $username = explode('@', $input['email'])[0];
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) $username .= rand(100, 999);

        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, phone, role, barangay_id, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            $username,
            $input['email'],
            password_hash($input['password'], PASSWORD_DEFAULT),
            $input['first_name'],
            $input['last_name'],
            $input['phone'] ?? null,
            $input['role'],
            $input['barangay_id'] ?? null
        ]);

        jsonResponse(['message' => 'User created', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'User ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];

        $allowed = ['first_name', 'last_name', 'phone', 'role', 'barangay_id', 'status'];
        foreach ($allowed as $f) {
            if (isset($input[$f])) {
                $fields[] = "$f = ?";
                $params[] = $input[$f];
            }
        }

        if (isset($input['password']) && !empty($input['password'])) {
            $fields[] = "password = ?";
            $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
        }

        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);

        jsonResponse(['message' => 'User updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
