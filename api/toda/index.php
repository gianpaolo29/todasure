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
                SELECT t.*, b.name as barangay_name,
                       (SELECT COUNT(*) FROM drivers d WHERE d.toda_id = t.id) as driver_count
                FROM todas t
                JOIN barangays b ON t.barangay_id = b.id
                WHERE t.id = ?
            ");
            $stmt->execute([$id]);
            $toda = $stmt->fetch();
            if (!$toda) jsonResponse(['error' => 'TODA not found'], 404);
            jsonResponse($toda);
        } else {
            $stmt = $pdo->query("
                SELECT t.*, b.name as barangay_name,
                       (SELECT COUNT(*) FROM drivers d WHERE d.toda_id = t.id) as driver_count
                FROM todas t
                JOIN barangays b ON t.barangay_id = b.id
                ORDER BY t.name
            ");
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        requireAdmin();
        $input = getInput();
        $required = ['name', 'barangay_id'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO todas (name, barangay_id, president, contact_number)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['name'],
            $input['barangay_id'],
            $input['president'] ?? null,
            $input['contact_number'] ?? null
        ]);

        jsonResponse(['message' => 'TODA created', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'TODA ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];
        $allowed = ['name', 'barangay_id', 'president', 'contact_number', 'status'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE todas SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        jsonResponse(['message' => 'TODA updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
