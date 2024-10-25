<?php
session_start();
include("db.php");

if (!isset($_SESSION['valid'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$channel_id = $_GET['channel_id'] ?? null;

if (!$channel_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Channel ID required']);
    exit();
}

try {
    $messages_stmt = $con->prepare("
        SELECT m.*, u.Username as username
        FROM messages m
        JOIN users u ON m.user_id = u.id
        WHERE m.channel_id = ?
        ORDER BY m.created_at ASC
        LIMIT 50
    ");
    $messages_stmt->execute([$channel_id]);
    $messages = $messages_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($messages);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}
