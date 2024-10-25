<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

try {
    require_once 'db.php';
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['channel_id']) || !isset($input['content'])) {
        throw new Exception('Missing required fields');
    }
    
    $channel_id = filter_var($input['channel_id'], FILTER_SANITIZE_NUMBER_INT);
    $content = htmlspecialchars($input['content'], ENT_QUOTES, 'UTF-8');
    $user_id = $_SESSION['user_id'];
    
    $stmt = $pdo->prepare("
        SELECT c.id 
        FROM channels c 
        JOIN server_members sm ON c.server_id = sm.server_id 
        WHERE c.id = ? AND sm.user_id = ?
    ");
    $stmt->execute([$channel_id, $user_id]);
    
    if (!$stmt->fetch()) {
        throw new Exception('Access denied to this channel');
    }
    
    $stmt = $pdo->prepare("
        INSERT INTO messages (channel_id, user_id, content, created_at) 
        VALUES (?, ?, ?, NOW())
    ");
    
    $stmt->execute([$channel_id, $user_id, $content]);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Message sent successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}