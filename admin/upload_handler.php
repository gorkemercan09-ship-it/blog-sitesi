<?php
// admin/upload_handler.php
require_once '../includes/functions.php';
checkLogin();

if (isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

    if (in_array($ext, $allowed)) {
        $filename = time() . "_" . bin2hex(random_bytes(4)) . "." . $ext;
        $target = "../uploads/" . $filename;

        if (move_uploaded_file($file['tmp_name'], $target)) {
            // TinyMCE expects a JSON response with 'location'
            echo json_encode(['location' => '../uploads/' . $filename]);
            exit;
        }
    }
}

header("HTTP/1.1 500 Internal Server Error");
echo json_encode(['error' => 'Yükleme başarısız.']);
?>