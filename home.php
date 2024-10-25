<?php 
session_start();
include("php/db.php");

if (!isset($_SESSION['valid'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['id'];

$user_stmt = $con->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user = $user_stmt->fetch(PDO::FETCH_ASSOC);

$servers_stmt = $con->prepare("
    SELECT s.*, 
           (SELECT COUNT(*) FROM server_members WHERE server_id = s.id) as member_count
    FROM servers s 
    JOIN server_members sm ON s.id = sm.server_id 
    WHERE sm.user_id = ?
");
$servers_stmt->execute([$user_id]);

$friends_stmt = $con->prepare("
    SELECT u.*, f.status
    FROM users u
    JOIN friendships f ON (u.id = f.user_id1 OR u.id = f.user_id2)
    WHERE (f.user_id1 = ? OR f.user_id2 = ?)
    AND f.status = 'accepted'
    AND u.id != ?
");
$friends_stmt->execute([$user_id, $user_id, $user_id]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blyoid - Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@200;300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/home.css">
</head>
<body>
    <div id="success-message"></div>

    <div class="dashboard">
        <div class="servers-sidebar">
            <?php while($server = $servers_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="server-icon" onclick="switchServer(<?php echo $server['id']; ?>)" title="<?php echo htmlspecialchars($server['name']); ?>">
                    <?php echo substr($server['name'], 0, 1); ?>
                </div>
            <?php endwhile; ?>
            <div class="server-icon create-server" onclick="showCreateServer()">
                <i class="fas fa-plus"></i>
            </div>
        </div>

        <div class="channels-sidebar">
            <div class="server-header">
                <span id="current-server">Select a Server</span>
                <button class="server-settings-btn" onclick="showServerSettings()" title="Server Settings">
                    <i class="fas fa-gear"></i>
                </button>
            </div>
            <div class="channels-list" id="channels-list">
                <!-- Default general channel will be added here -->
            </div>
            <div class="user-area">
                <div class="user-avatar"></div>
                <div class="user-info">
                    <div><?php echo htmlspecialchars($user['Username']); ?></div>
                    <div class="user-status">Online</div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <div class="chat-area" id="chat-area">
                <!-- Messages will be loaded here -->
            </div>
            <div class="message-input">
                <textarea placeholder="Type a message..." rows="1" id="message-input"></textarea>
            </div>
        </div>

        <div class="friends-sidebar">
            <h3>Friends</h3>
            <?php while($friend = $friends_stmt->fetch(PDO::FETCH_ASSOC)): ?>
                <div class="friend-item">
                    <div class="user-avatar"></div>
                    <div>
                        <div><?php echo htmlspecialchars($friend['Username']); ?></div>
                        <div class="user-status">
                            <?php echo $friend['status'] ?? 'Offline'; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <div class="modal" id="create-server-modal">
        <div class="modal-content">
            <h2>Create a Server</h2>
            <form id="create-server-form">
                <input type="text" name="server_name" placeholder="Server Name" required>
                <textarea name="description" placeholder="Server Description"></textarea>
                <button type="submit" class="btn btn-primary">Create Server</button>
                <button type="button" class="btn btn-secondary" onclick="hideModal()">Cancel</button>
            </form>
        </div>
    </div>

    <div class="modal" id="server-settings-modal">
        <div class="modal-content">
            <div class="settings-sidebar">
                <div class="settings-nav">
                    <button class="settings-nav-item active" data-tab="overview">Overview</button>
                    <button class="settings-nav-item" data-tab="channels">Channels</button>
                    <button class="settings-nav-item" data-tab="members">Members</button>
                </div>
            </div>
            
            <div class="settings-content">
                <div class="settings-tab active" id="overview-tab">
                    <h2>Server Overview</h2>
                    <form id="server-settings-form">
                        <div class="form-group">
                            <label>Server Name</label>
                            <input type="text" name="server_name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Server Icon</label>
                            <input type="file" name="icon" accept="image/*">
                            <div class="icon-preview"></div>
                        </div>
                        
                        <div class="form-group">
                            <label>Server Banner</label>
                            <input type="file" name="banner" accept="image/*">
                            <div class="banner-preview"></div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
                </div>
                
                <div class="settings-tab" id="channels-tab">
                    <h2>Channels</h2>
                    <div class="channels-list-settings"></div>
                    <button class="btn btn-primary" onclick="showCreateChannel()">Create Channel</button>
                </div>
                
                <div class="settings-tab" id="members-tab">
                    <h2>Members</h2>
                    <div class="members-list-settings"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="create-channel-modal">
        <div class="modal-content">
            <h2>Create Channel</h2>
            <form id="create-channel-form">
                <input type="text" name="channel_name" placeholder="Channel Name" required>
                <select name="channel_type">
                    <option value="text">Text Channel</option>
                    <option value="voice">Voice Channel</option>
                </select>
                <button type="submit" class="btn btn-primary">Create Channel</button>
                <button type="button" class="btn btn-secondary" onclick="hideModal('create-channel-modal')">Cancel</button>
            </form>
        </div>
    </div>

    <script src="scripts/home.js"></script>
</body>
</html>
