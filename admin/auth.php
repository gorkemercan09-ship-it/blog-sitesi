<?php
// admin/auth.php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];

    $database = new Database();
    $db = $database->getConnection();

    $query = "SELECT * FROM admins WHERE username = :username LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        // Normalde password_verify kullanmalıyız. 
        // Şimdilik test için admin123 kontrolü yapalım veya database.sql'deki hash ile karşılaştıralım.
        // Veritabanındaki hash: $2y$10$QOQIDIsQpYq8y... (admin123 için bcrypt hash)

        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_user'] = $admin['username'];

            // Son giriş zamanını güncelle
            $update = "UPDATE admins SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id";
            $uStmt = $db->prepare($update);
            $uStmt->bindParam(':id', $admin['id']);
            $uStmt->execute();

            $_SESSION['login_welcome'] = true;

            redirect('index.php');
        } else {
            redirect('login.php?error=1');
        }
    } else {
        redirect('login.php?error=1');
    }
} else if (isset($_GET['logout'])) {
    session_destroy();
    redirect('login.php');
} else {
    redirect('login.php');
}
