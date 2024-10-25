<?php
session_start();
include("db.php");

if (!isset($_SESSION['valid'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$server_id = $_GET['server_id'] ?? null;

if (!$server_id) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server ID required']);
    exit();
}

try {
    $server_stmt = $con->prepare("
        SELECT * FROM servers WHERE id = ?
    ");
    $server_stmt->execute([$server_id]);
    $server = $server_stmt->fetch(PDO::FETCH_ASSOC);

    $channels_stmt = $con->prepare("
        SELECT * FROM channels 
        WHERE server_id = ?
        ORDER BY name ASC
    ");
    $channels_stmt->execute([$server_id]);
    $channels = $channels_stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode([
        'server' => $server,
        'channels' => $channels
    ]);
} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database error']);
}