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
                SELECT v.*, d.first_name, d.last_name
                FROM violations v
                JOIN drivers d ON v.driver_id = d.id
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            $violation = $stmt->fetch();
            if (!$violation) jsonResponse(['error' => 'Violation not found'], 404);
            jsonResponse($violation);
        } else {
            $driver_id = $_GET['driver_id'] ?? '';
            $status = $_GET['status'] ?? '';
            $type = $_GET['type'] ?? '';

            $sql = "SELECT v.*, d.first_name, d.last_name
                    FROM violations v
                    JOIN drivers d ON v.driver_id = d.id
                    WHERE 1=1";
            $params = [];

            if ($driver_id) {
                $sql .= " AND v.driver_id = ?";
                $params[] = $driver_id;
            }
            if ($status) {
                $sql .= " AND v.status = ?";
                $params[] = $status;
            }
            if ($type) {
                $sql .= " AND v.violation_type = ?";
                $params[] = $type;
            }
            $sql .= " ORDER BY v.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        requireAdmin();
        $input = getInput();
        $required = ['driver_id', 'violation_type', 'severity'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO violations (driver_id, trip_id, complaint_id, violation_type, description, severity, penalty)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['driver_id'],
            $input['trip_id'] ?? null,
            $input['complaint_id'] ?? null,
            $input['violation_type'],
            $input['description'] ?? null,
            $input['severity'],
            $input['penalty'] ?? null
        ]);

        jsonResponse(['message' => 'Violation recorded', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Violation ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];

        $allowed = ['status', 'severity', 'penalty', 'description'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE violations SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);

        jsonResponse(['message' => 'Violation updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
