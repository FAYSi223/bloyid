<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

try {
    require_once 'db.php';
    
    if (!isset($_POST['server_id']) || !isset($_POST['channel_name'])) {
        throw new Exception('Missing required fields');
    }
    
    $server_id = filter_var($_POST['server_id'], FILTER_SANITIZE_NUMBER_INT);
    $channel_name = preg_replace('/[^a-z0-9\-_]/', '', strtolower($_POST['channel_name']));
    $description = isset($_POST['description']) ? 
        htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8') : '';
    
    $stmt = $pdo->prepare("
        SELECT role 
        FROM server_members 
        WHERE server_id = ? AND user_id = ? AND role IN ('admin', 'moderator')
    ");
    $stmt->execute([$server_id, $_SESSION['user_id']]);
    
    if (!$stmt->fetch()) {
        throw new Exception('You do not have permission to create channels');
    }
    
    $stmt = $pdo->prepare("
        SELECT id 
        FROM channels 
        WHERE server_id = ? AND name = ?
    ");
    $stmt->execute([$server_id, $channel_name]);
    
    if ($stmt->fetch()) {
        throw new Exception('A channel with this name already exists');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO channels (server_id, name, description, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$server_id, $channel_name, $description]);
    
    $channel_id = $pdo->lastInsertId();
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Channel created successfully',
        'channel' => [
            'id' => $channel_id,
            'name' => $channel_name,
            'description' => $description
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}