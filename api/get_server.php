<?php
session_start();
include('../php/db.php');

if (!isset($_SESSION['id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Server ID is required"]);
    exit();
}

try {
    $server_id = $_GET['id'];
    $user_id = $_SESSION['id'];

    $member_check = $con->prepare("
        SELECT 1 FROM server_members 
        WHERE server_id = ? AND user_id = ?
    ");
    $member_check->execute([$server_id, $user_id]);

    if (!$member_check->fetch()) {
        http_response_code(403);
        echo json_encode(["status" => "error", "message" => "Access denied"]);
        exit();
    }

    $stmt = $con->prepare("
        SELECT id, name, description, banner_url, icon_url, owner_id
        FROM servers 
        WHERE id = ?
    ");
    $stmt->execute([$server_id]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$server) {
        http_response_code(404);
        echo json_encode(["status" => "error", "message" => "Server not found"]);
        exit();
    }

    echo json_encode([
        "status" => "success",
        "data" => $server
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>