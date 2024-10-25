<?php
session_start();
include("php/db.php");

if(!isset($_GET['code'])) {
    header("Location: index.php");
    exit();
}

$invite_code = mysqli_real_escape_string($con, $_GET['code']);

if(isset($_SESSION['valid'])) {
    $user_id = $_SESSION['id'];
    
    $invite_query = mysqli_query($con, "SELECT * FROM server_invites WHERE code='$invite_code' AND expires_at > NOW()");
    
    if($invite = mysqli_fetch_assoc($invite_query)) {
        $server_id = $invite['server_id'];
        
        $member_check = mysqli_query($con, "SELECT * FROM server_members WHERE user_id=$user_id AND server_id=$server_id");
        
        if(mysqli_num_rows($member_check) == 0) {
            mysqli_query($con, "INSERT INTO server_members (user_id, server_id) VALUES ($user_id, $server_id)");
        }
        
        header("Location: home.php");
        exit();
    } else {
        die("Invalid or expired invite");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Server - Blyoid</title>
    <link rel="stylesheet" href="style/invite.css">
</head>
<body>
    <div class="card">
        <?php
        if($invite = mysqli_fetch_assoc($invite_query)) {
            $server_id = $invite['server_id'];
            $server_query = mysqli_query($con, "SELECT * FROM servers WHERE id=$server_id");
            $server = mysqli_fetch_assoc($server_query);
            
            echo "<h1 class='title'>You've got invited!</h1>";
            echo "<div class='avatar'></div>";
            echo "<div class='server-info'>";
            echo "<div class='server-name'>" . htmlspecialchars($server['name']) . "</div>";
            echo "<div class='server-subtitle'>by " . htmlspecialchars($server['owner_name']) . "</div>";
            echo "</div>";
            
            echo "<a href='index.php?redirect=invite.php?code=" . $invite_code . "' class='join-button'>LOGIN TO JOIN</a>";
        } else {
            echo "<h1 class='title'>Invalid Invite</h1>";
            echo "<p style='color: white;'>This invite is invalid or has expired.</p>";
        }
        ?>
    </div>
</body>^1
</html>
