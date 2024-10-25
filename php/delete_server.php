<?php

require_once 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php/input'), true);
$server_id = $data['server_id'] ?? null;

if (!$server_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Server ID is required']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    $tables = ['channels', 'server_members', 'server_roles'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("DELETE FROM $table WHERE server_id = ?");
        $stmt->execute([$server_id]);
    }
    
    $stmt = $pdo->prepare("DELETE FROM servers WHERE id = ?");
    $stmt->execute([$server_id]);
    
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete server']);
    error_log($e->getMessage());
}
?>