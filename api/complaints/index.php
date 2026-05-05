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
                SELECT c.*, d.first_name, d.last_name, tr.plate_number, tr.body_number
                FROM complaints c
                JOIN drivers d ON c.driver_id = d.id
                JOIN tricycles tr ON c.tricycle_id = tr.id
                WHERE c.id = ?
            ");
            $stmt->execute([$id]);
            $complaint = $stmt->fetch();
            if (!$complaint) jsonResponse(['error' => 'Complaint not found'], 404);
            jsonResponse($complaint);
        } else {
            $driver_id = $_GET['driver_id'] ?? '';
            $status = $_GET['status'] ?? '';

            $sql = "SELECT c.*, d.first_name, d.last_name, tr.plate_number
                    FROM complaints c
                    JOIN drivers d ON c.driver_id = d.id
                    JOIN tricycles tr ON c.tricycle_id = tr.id
                    WHERE 1=1";
            $params = [];

            if ($driver_id) {
                $sql .= " AND c.driver_id = ?";
                $params[] = $driver_id;
            }
            if ($status) {
                $sql .= " AND c.status = ?";
                $params[] = $status;
            }
            $sql .= " ORDER BY c.created_at DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        // Public endpoint - passengers can submit without auth
        $input = getInput();
        $required = ['tricycle_id', 'complaint_type', 'description'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        // Get driver from tricycle
        $stmt = $pdo->prepare("SELECT driver_id FROM tricycles WHERE id = ?");
        $stmt->execute([$input['tricycle_id']]);
        $tricycle = $stmt->fetch();
        if (!$tricycle) jsonResponse(['error' => 'Tricycle not found'], 404);

        $stmt = $pdo->prepare("
            INSERT INTO complaints (tricycle_id, driver_id, trip_id, passenger_name, passenger_contact, complaint_type, description)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['tricycle_id'],
            $tricycle['driver_id'],
            $input['trip_id'] ?? null,
            $input['passenger_name'] ?? null,
            $input['passenger_contact'] ?? null,
            $input['complaint_type'],
            $input['description']
        ]);

        $complaintId = $pdo->lastInsertId();

        // Create notification for admin
        try {
            $pdo->prepare("INSERT INTO notifications (role_target, type, title, message, link) VALUES ('admin', 'complaint', 'New Complaint Filed', ?, 'complaints.html')")
                ->execute([$input['complaint_type'] . ' - ' . substr($input['description'], 0, 80)]);
        } catch(Exception $e) {}

        jsonResponse(['message' => 'Complaint submitted successfully', 'id' => $complaintId], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Complaint ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];

        $allowed = ['status', 'admin_notes'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE complaints SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);

        // Auto-create violation if complaint is confirmed
        if (isset($input['status']) && $input['status'] === 'resolved') {
            $stmt = $pdo->prepare("SELECT driver_id, trip_id FROM complaints WHERE id = ?");
            $stmt->execute([$id]);
            $complaint = $stmt->fetch();

            if (isset($input['create_violation']) && $input['create_violation']) {
                $stmt = $pdo->prepare("
                    INSERT INTO violations (driver_id, trip_id, complaint_id, violation_type, description, severity)
                    VALUES (?, ?, ?, 'complaint_based', ?, ?)
                ");
                $stmt->execute([
                    $complaint['driver_id'],
                    $complaint['trip_id'],
                    $id,
                    $input['violation_description'] ?? 'Complaint-based violation',
                    $input['violation_severity'] ?? 'minor'
                ]);
            }
        }

        jsonResponse(['message' => 'Complaint updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
