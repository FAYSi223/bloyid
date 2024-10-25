<?php
require_once 'db.php';

header('Content-Type: application/json');

if (!isset($_GET['server_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Server ID is required']);
    exit;
}

$server_id = intval($_GET['server_id']);

try {
    $stmt = $pdo->prepare("
        SELECT id, name, type, position
        FROM channels
        WHERE server_id = ?
        ORDER BY position ASC
    ");
    $stmt->execute([$server_id]);
    $channels = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($channels);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    error_log($e->getMessage());
}
?>