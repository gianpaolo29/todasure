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
            $stmt = $pdo->prepare("SELECT * FROM barangays WHERE id = ?");
            $stmt->execute([$id]);
            $barangay = $stmt->fetch();
            if (!$barangay) jsonResponse(['error' => 'Barangay not found'], 404);
            jsonResponse($barangay);
        } else {
            $stmt = $pdo->query("SELECT * FROM barangays ORDER BY name");
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        requireAdmin();
        $input = getInput();
        if (empty($input['name'])) {
            jsonResponse(['error' => 'Barangay name is required'], 400);
        }

        $stmt = $pdo->prepare("INSERT INTO barangays (name, municipality, province) VALUES (?, ?, ?)");
        $stmt->execute([
            $input['name'],
            $input['municipality'] ?? '',
            $input['province'] ?? ''
        ]);
        jsonResponse(['message' => 'Barangay added', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Barangay ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];
        $allowed = ['name', 'municipality', 'province', 'status'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE barangays SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        jsonResponse(['message' => 'Barangay updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
