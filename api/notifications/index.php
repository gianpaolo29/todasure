<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
$pdo = getConnection();
$method = getMethod();
$session = requireAuth();

switch ($method) {
    case 'GET':
        $role = $session['role'];
        $userId = $session['user_id'];

        // Get notifications for this user or role
        $stmt = $pdo->prepare("
            SELECT * FROM notifications
            WHERE (user_id = ? OR (user_id IS NULL AND role_target = ?))
            ORDER BY created_at DESC
            LIMIT 30
        ");
        $stmt->execute([$userId, $role]);
        $notifs = $stmt->fetchAll();

        // Count unread
        $stmt2 = $pdo->prepare("
            SELECT COUNT(*) as unread FROM notifications
            WHERE (user_id = ? OR (user_id IS NULL AND role_target = ?)) AND is_read = 0
        ");
        $stmt2->execute([$userId, $role]);
        $count = $stmt2->fetch();

        jsonResponse([
            'notifications' => $notifs,
            'unread_count' => intval($count['unread'])
        ]);
        break;

    case 'PUT':
        $id = getId();
        $input = getInput();

        if (isset($input['mark_all_read']) && $input['mark_all_read']) {
            $stmt = $pdo->prepare("
                UPDATE notifications SET is_read = 1
                WHERE (user_id = ? OR (user_id IS NULL AND role_target = ?)) AND is_read = 0
            ");
            $stmt->execute([$session['user_id'], $session['role']]);
            jsonResponse(['message' => 'All notifications marked as read']);
        } elseif ($id) {
            $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ?");
            $stmt->execute([$id]);
            jsonResponse(['message' => 'Notification marked as read']);
        } else {
            jsonResponse(['error' => 'No action specified'], 400);
        }
        break;

    default:
        jsonResponse(['error' => 'Method not allowed'], 405);
}
