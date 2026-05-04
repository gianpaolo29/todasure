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
                SELECT tr.*, d.first_name, d.last_name
                FROM tricycles tr
                LEFT JOIN drivers d ON tr.driver_id = d.id
                WHERE tr.id = ?
            ");
            $stmt->execute([$id]);
            $tricycle = $stmt->fetch();
            if (!$tricycle) jsonResponse(['error' => 'Tricycle not found'], 404);
            jsonResponse($tricycle);
        } else {
            $driver_id = $_GET['driver_id'] ?? '';
            $sql = "SELECT tr.*, d.first_name, d.last_name
                    FROM tricycles tr
                    LEFT JOIN drivers d ON tr.driver_id = d.id
                    WHERE 1=1";
            $params = [];
            if ($driver_id) {
                $sql .= " AND tr.driver_id = ?";
                $params[] = $driver_id;
            }
            $sql .= " ORDER BY tr.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        requireAdmin();
        $input = getInput();
        $required = ['plate_number', 'body_number'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO tricycles (driver_id, plate_number, body_number, color, model)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['driver_id'] ?? null,
            $input['plate_number'],
            $input['body_number'],
            $input['color'] ?? null,
            $input['model'] ?? null
        ]);

        jsonResponse(['message' => 'Tricycle registered', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Tricycle ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];
        $allowed = ['driver_id', 'plate_number', 'body_number', 'color', 'model', 'status'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE tricycles SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        jsonResponse(['message' => 'Tricycle updated']);
        break;

    case 'DELETE':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Tricycle ID required'], 400);

        // Check if tricycle has active trips
        $stmt = $pdo->prepare("SELECT COUNT(*) as c FROM trips WHERE tricycle_id = ? AND status = 'active'");
        $stmt->execute([$id]);
        if ($stmt->fetch()['c'] > 0) {
            jsonResponse(['error' => 'Cannot delete tricycle with active trips'], 400);
        }

        // Soft delete - set status to inactive
        $stmt = $pdo->prepare("UPDATE tricycles SET status = 'inactive' WHERE id = ?");
        $stmt->execute([$id]);
        jsonResponse(['message' => 'Tricycle deactivated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
