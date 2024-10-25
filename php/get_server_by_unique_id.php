<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

if (!isset($_GET['unique_id']) || strlen($_GET['unique_id']) !== 10) {
    echo json_encode(["status" => "error", "message" => "Invalid server ID"]);
    exit();
}

try {
    $stmt = $con->prepare("
        SELECT s.*, sm.role 
        FROM servers s
        JOIN server_members sm ON s.id = sm.server_id
        WHERE s.unique_id = :unique_id AND sm.user_id = :user_id
    ");
    
    $stmt->execute([
        ':unique_id' => $_GET['unique_id'],
        ':user_id' => $_SESSION['id']
    ]);
    
    $server = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$server) {
        echo json_encode(["status" => "error", "message" => "Server not found or access denied"]);
        exit();
    }
    
    echo json_encode([
        "status" => "success",
        "server" => $server
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage()
    ]);
}
?>