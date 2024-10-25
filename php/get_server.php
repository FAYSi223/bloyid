<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Server ID is required']);
    exit;
}

$server_id = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("
        SELECT id, name, description, icon_url, banner_url 
        FROM servers 
        WHERE id = ?
    ");
    $stmt->execute([$server_id]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        http_response_code(404);
        echo json_encode(['error' => 'Server not found']);
        exit;
    }
    
    echo json_encode($server);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log($e->getMessage());
}
?>