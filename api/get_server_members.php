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
        SELECT 
            u.id,
            u.Username,
            CASE 
                WHEN TIMESTAMPDIFF(MINUTE, u.last_active, NOW()) <= 5 THEN 'online'
                ELSE 'offline'
            END as status,
            sm.role
        FROM users u
        JOIN server_members sm ON u.id = sm.user_id
        WHERE sm.server_id = ?
        ORDER BY 
            CASE sm.role 
                WHEN 'owner' THEN 1 
                WHEN 'admin' THEN 2
                ELSE 3 
            END,
            u.Username
    ");
    $stmt->execute([$server_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $members
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Server error: " . $e->getMessage()
    ]);
}
?>
