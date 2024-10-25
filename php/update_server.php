<?php
require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$server_id = $_POST['server_id'] ?? null;
if (!$server_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Server ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $stmt = $pdo->prepare("
        UPDATE servers 
        SET name = ?, description = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $_POST['server_name'],
        $_POST['description'],
        $server_id
    ]);
    
    if (isset($_FILES['icon_upload']) && $_FILES['icon_upload']['error'] === UPLOAD_ERR_OK) {
        $icon_url = handleFileUpload($_FILES['icon_upload'], 'icons');
        $stmt = $pdo->prepare("UPDATE servers SET icon_url = ? WHERE id = ?");
        $stmt->execute([$icon_url, $server_id]);
    }
    
    if (isset($_FILES['banner_upload']) && $_FILES['banner_upload']['error'] === UPLOAD_ERR_OK) {
        $banner_url = handleFileUpload($_FILES['banner_upload'], 'banners');
        $stmt = $pdo->prepare("UPDATE servers SET banner_url = ? WHERE id = ?");
        $stmt->execute([$banner_url, $server_id]);
    }
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to update server']);
    error_log($e->getMessage());
}

function handleFileUpload($file, $subfolder) {
    $upload_dir = "../uploads/$subfolder/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $file_name = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $file_name;
    
    if (!move_uploaded_file($file['tmp_name'], $file_path)) {
        throw new Exception('Failed to upload file');
    }
    
    return "/uploads/$subfolder/$file_name";
}
?>