<?php
session_start();
include 'db.php';

if (!isset($_SESSION['id'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit();
}

$user_id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $server_name = trim($_POST['server_name'] ?? '');
        $server_description = trim($_POST['description'] ?? '');

        if (empty($server_name)) {
            throw new Exception("Server name is required.");
        }

        $con->beginTransaction();

        $server_stmt = $con->prepare("
            INSERT INTO servers (name, description, owner_id) 
            VALUES (:name, :description, :owner_id)
        ");
        
        $server_stmt->execute([
            ':name' => $server_name,
            ':description' => $server_description,
            ':owner_id' => $user_id
        ]);
        
        $server_id = $con->lastInsertId();
        
        $member_stmt = $con->prepare("
            INSERT INTO server_members (server_id, user_id, role) 
            VALUES (:server_id, :user_id, 'owner')
        ");
        
        $member_stmt->execute([
            ':server_id' => $server_id,
            ':user_id' => $user_id
        ]);

        $channel_stmt = $con->prepare("
            INSERT INTO channels (server_id, name, type) 
            VALUES (:server_id, 'general', 'text')
        ");
        
        $channel_stmt->execute([
            ':server_id' => $server_id
        ]);

        $con->commit();
        
        echo json_encode([
            "status" => "success", 
            "message" => "Server created successfully!", 
            "server_id" => $server_id
        ]);

    } catch (PDOException $e) {
        if ($con->inTransaction()) {
            $con->rollBack();
        }
        
        echo json_encode([
            "status" => "error",
            "message" => "Database error: " . $e->getMessage()
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            "status" => "error",
            "message" => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        "status" => "error", 
        "message" => "Invalid request method."
    ]);
}
?>
