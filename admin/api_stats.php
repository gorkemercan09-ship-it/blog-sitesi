<?php
// admin/api_stats.php
require_once '../includes/functions.php';
checkLogin();
require_once '../includes/db.php';

$database = new Database();
$db = $database->getConnection();

$posts_count = $db->query("SELECT COUNT(*) FROM posts")->fetchColumn();
$cats_count = $db->query("SELECT COUNT(*) FROM categories")->fetchColumn();
$total_views = $db->query("SELECT SUM(views_count) FROM posts")->fetchColumn() ?? 0;
$traffic_today = $db->query("SELECT COUNT(*) FROM traffic WHERE DATE(visited_at) = CURDATE()")->fetchColumn();

header('Content-Type: application/json');
echo json_encode([
    'posts_count' => $posts_count,
    'cats_count' => $cats_count,
    'total_views' => $total_views,
    'traffic_today' => $traffic_today
]);
exit;
