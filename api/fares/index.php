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
                SELECT fr.*, b.name as barangay_name
                FROM fare_rates fr
                JOIN barangays b ON fr.barangay_id = b.id
                WHERE fr.id = ?
            ");
            $stmt->execute([$id]);
            $rate = $stmt->fetch();
            if (!$rate) jsonResponse(['error' => 'Fare rate not found'], 404);
            jsonResponse($rate);
        } else {
            $barangay_id = $_GET['barangay_id'] ?? '';
            $sql = "SELECT fr.*, b.name as barangay_name
                    FROM fare_rates fr
                    JOIN barangays b ON fr.barangay_id = b.id
                    WHERE 1=1";
            $params = [];

            if ($barangay_id) {
                $sql .= " AND fr.barangay_id = ?";
                $params[] = $barangay_id;
            }
            $sql .= " ORDER BY fr.effective_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            jsonResponse($stmt->fetchAll());
        }
        break;

    case 'POST':
        requireAdmin();
        $input = getInput();
        $required = ['barangay_id', 'base_fare', 'base_distance', 'per_km_rate', 'effective_date'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                jsonResponse(['error' => "Field '$field' is required"], 400);
            }
        }

        // Deactivate previous rate for this barangay
        $stmt = $pdo->prepare("UPDATE fare_rates SET status = 'inactive' WHERE barangay_id = ? AND status = 'active'");
        $stmt->execute([$input['barangay_id']]);

        $stmt = $pdo->prepare("
            INSERT INTO fare_rates (barangay_id, base_fare, base_distance, per_km_rate, discount_senior, effective_date)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $input['barangay_id'],
            $input['base_fare'],
            $input['base_distance'],
            $input['per_km_rate'],
            $input['discount_senior'] ?? 20.00,
            $input['effective_date']
        ]);

        jsonResponse(['message' => 'Fare rate created', 'id' => $pdo->lastInsertId()], 201);
        break;

    case 'PUT':
        requireAdmin();
        if (!$id) jsonResponse(['error' => 'Fare rate ID required'], 400);

        $input = getInput();
        $fields = [];
        $params = [];

        $allowed = ['base_fare', 'base_distance', 'per_km_rate', 'discount_senior', 'effective_date', 'status'];
        foreach ($allowed as $field) {
            if (isset($input[$field])) {
                $fields[] = "$field = ?";
                $params[] = $input[$field];
            }
        }
        if (empty($fields)) jsonResponse(['error' => 'No fields to update'], 400);

        $params[] = $id;
        $stmt = $pdo->prepare("UPDATE fare_rates SET " . implode(', ', $fields) . " WHERE id = ?");
        $stmt->execute($params);
        jsonResponse(['message' => 'Fare rate updated']);
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
