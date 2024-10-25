<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

try {
    require_once 'db_connect.php';
    
    if (!isset($_POST['server_id']) || !isset($_POST['server_name'])) {
        throw new Exception('Missing required fields');
    }
    
    $server_id = filter_var($_POST['server_id'], FILTER_SANITIZE_NUMBER_INT);
    $server_name = htmlspecialchars($_POST['server_name'], ENT_QUOTES, 'UTF-8');
    $description = isset($_POST['description']) ? 
        htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : '';
    
    $stmt = $pdo->prepare("
        SELECT role 
        FROM server_members 
        WHERE server_id = ? AND user_id = ? AND role = 'admin'
    ");
    $stmt->execute([$server_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('You do not have permission to update this server');
    }
    
    $icon_url = null;
    $banner_url = null;
    
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
        $icon_url = handleFileUpload($_FILES['icon'], 'server_icons');
    }
    
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $banner_url = handleFileUpload($_FILES['banner'], 'server_banners');
    }
    
    $sql = "UPDATE servers SET name = ?, description = ?";
    $params = [$server_name, $description];
    
    if ($icon_url) {
        $sql .= ", icon_url = ?";
        $params[] = $icon_url;
    }
    
    if ($banner_url) {
        $sql .= ", banner_url = ?";
        $params[] = $banner_url;
    }
    
    $sql .= " WHERE id = ?";
    $params[] = $server_id;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Server updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}

function handleFileUpload($file, $directory) {
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed)) {
        throw new Exception('Invalid file type');
    }
    
    $max_size = 5 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        throw new Exception('File too large');
    }
    
    $upload_dir = "../uploads/$directory/";
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $filepath = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Failed to upload file');
    }
    
    return "/uploads/$directory/" . $filename;
}
