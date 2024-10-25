<?php
$host = "sql304.infinityfree.com";
$dbname = "if0_37536001_bloyid";
$user = "if0_37536001";
$pass = "298612Jasonn";

$conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

$username = $_SESSION['username'];

$stmt = $conn->prepare("
    SELECT servers.* FROM servers
    JOIN server_members ON servers.id = server_members.server_id
    WHERE server_members.username = :username
");
$stmt->execute(['username' => $username]);
$servers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['delete_server'])) {
    $server_id = $_POST['server_id'];

    $stmt = $conn->prepare("SELECT owner FROM servers WHERE id = :server_id");
    $stmt->execute(['server_id' => $server_id]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($server && $server['owner'] === $username) {
        $stmt = $conn->prepare("DELETE FROM servers WHERE id = :server_id");
        $stmt->execute(['server_id' => $server_id]);
        
        $stmt = $conn->prepare("DELETE FROM server_members WHERE server_id = :server_id");
        $stmt->execute(['server_id' => $server_id]);

        echo "Server deleted successfully!";
        exit;
    } else {
        echo "You do not have permission to delete this server.";
    }
}

if (isset($_POST['rename_server'])) {
    $server_id = $_POST['server_id'];
    $new_name = $_POST['new_name'];

    $stmt = $conn->prepare("SELECT owner FROM servers WHERE id = :server_id");
    $stmt->execute(['server_id' => $server_id]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($server && $server['owner'] === $username) {
        $stmt = $conn->prepare("UPDATE servers SET name = :new_name WHERE id = :server_id");
        $stmt->execute(['new_name' => $new_name, 'server_id' => $server_id]);

        echo "Server renamed successfully!";
        exit;
    } else {
        echo "You do not have permission to rename this server.";
    }
}

if (isset($_POST['create_invite'])) {
    $server_id = $_POST['server_id'];

    $stmt = $conn->prepare("SELECT owner FROM servers WHERE id = :server_id");
    $stmt->execute(['server_id' => $server_id]);
    $server = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($server && $server['owner'] === $username) {
        $invite_code = bin2hex(random_bytes(5));

        $stmt = $conn->prepare("INSERT INTO invites (server_id, invite_code) VALUES (:server_id, :invite_code)");
        $stmt->execute(['server_id' => $server_id, 'invite_code' => $invite_code]);

        $invite_link = "https://bloyid.wuaze.com/join.php?code=" . $invite_code;

        echo "Invite created: <a href='" . htmlspecialchars($invite_link) . "'>" . htmlspecialchars($invite_link) . "</a>";
        exit;
    } else {
        echo "You do not have permission to create an invite for this server.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Settings</title>
    <link rel="stylesheet" href="style/home.css">
</head>
<body>

<h1>Server Settings</h1>

<?php foreach ($servers as $server): ?>
    <div class="server">
        <h2><?php echo htmlspecialchars($server['name']); ?></h2>
        <form method="POST">
            <input type="hidden" name="server_id" value="<?php echo $server['id']; ?>">
            <input type="text" name="new_name" placeholder="New server name" required>
            <button type="submit" name="rename_server">Rename Server</button>
        </form>
        <form method="POST">
            <input type="hidden" name="server_id" value="<?php echo $server['id']; ?>">
            <button type="submit" name="delete_server" style="background-color: #dc143c;">Delete Server</button>
        </form>
        <form method="POST">
            <input type="hidden" name="server_id" value="<?php echo $server['id']; ?>">
            <button type="submit" name="create_invite">Create Invite</button>
        </form>
    </div>
<?php endforeach; ?>

</body>
</html>
