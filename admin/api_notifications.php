<?php
// admin/api_notifications.php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Güvenlik amaçlı sadece AJAX isteklerini kabul et eklenebilir, ancak şimdilik admin session kontrolü yeterli
session_start();
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'fetch':
        // Okunmamış bildirim sayısını al
        $stmt_count = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0");
        $unread_count = $stmt_count->fetchColumn();

        // Son 10 bildirimi al
        $stmt_list = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
        $notifications = $stmt_list->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'unread_count' => $unread_count, 'notifications' => $notifications]);
        break;

    case 'mark_read':
        // Bildirim menüsü açıldığında tümünü (veya son görünenleri) okundu olarak işaretle
        $db->query("UPDATE notifications SET is_read = 1 WHERE is_read = 0");
        echo json_encode(['success' => true]);
        break;

    case 'clear_all':
        // Tüm bildirimleri sil
        $db->query("TRUNCATE TABLE notifications"); // Veya DELETE FROM notifications
        echo json_encode(['success' => true]);
        break;

    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}
