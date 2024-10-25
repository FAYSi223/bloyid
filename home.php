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

<!-- Server Settings Modal -->
<div class="ss-modal" id="server-settings-modal">
        <div class="ss-modal-content">
            <div class="ss-modal-header">
                <h2>Server Settings</h2>
                <button class="ss-close-button" onclick="hideModal('server-settings-modal')">&times;</button>
            </div>
            
            <div class="ss-container">
                <div class="ss-sidebar">
                    <div class="ss-nav">
                        <button class="ss-nav-item active" data-tab="overview">
                            <i class="fas fa-home"></i>Overview
                        </button>
                        <button class="ss-nav-item" data-tab="channels">
                            <i class="fas fa-hashtag"></i>Channels
                        </button>
                        <button class="ss-nav-item" data-tab="members">
                            <i class="fas fa-users"></i>Members
                        </button>
                        <button class="ss-nav-item" data-tab="roles">
                            <i class="fas fa-user-shield"></i>Roles
                        </button>
                        <button class="ss-nav-item danger" data-tab="delete">
                            <i class="fas fa-trash"></i>Delete Server
                        </button>
                    </div>
                </div>
                
                <div class="ss-content">
                    <!-- Overview Tab -->
                    <div class="ss-tab active" id="overview-tab">
                        <form id="server-settings-form" class="ss-form">
                            <div class="ss-form-group">
                                <label for="server_name">Server Name</label>
                                <input type="text" id="server_name" name="server_name" class="ss-input" required>
                                <span class="ss-form-hint">Choose a name for your server</span>
                            </div>
                            
                            <div class="ss-form-group">
                                <label for="description">Description</label>
                                <textarea id="description" name="description" class="ss-textarea" rows="3"></textarea>
                                <span class="ss-form-hint">Tell members what this server is about</span>
                            </div>
                            
                            <div class="ss-form-group">
                                <label>Server Icon</label>
                                <div class="ss-media-upload">
                                    <div class="ss-icon-preview" id="icon-preview"></div>
                                    <div class="ss-upload-controls">
                                        <label class="ss-upload-button" for="icon-upload">
                                            <i class="fas fa-upload"></i> Upload Icon
                                        </label>
                                        <input type="file" id="icon-upload" name="icon" accept="image/*" hidden>
                                        <button type="button" class="ss-remove-button" id="remove-icon" hidden>
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    <span class="ss-form-hint">Recommended size: 512x512px</span>
                                </div>
                            </div>
                            
                            <div class="ss-form-group">
                                <label>Server Banner</label>
                                <div class="ss-media-upload">
                                    <div class="ss-banner-preview" id="banner-preview"></div>
                                    <div class="ss-upload-controls">
                                        <label class="ss-upload-button" for="banner-upload">
                                            <i class="fas fa-upload"></i> Upload Banner
                                        </label>
                                        <input type="file" id="banner-upload" name="banner" accept="image/*" hidden>
                                        <button type="button" class="ss-remove-button" id="remove-banner" hidden>
                                            <i class="fas fa-trash"></i> Remove
                                        </button>
                                    </div>
                                    <span class="ss-form-hint">Recommended size: 960x540px</span>
                                </div>
                            </div>
                            
                            <div class="ss-form-actions">
                                <button type="submit" class="ss-btn ss-btn-primary">Save Changes</button>
                                <button type="button" class="ss-btn ss-btn-secondary" onclick="hideModal('server-settings-modal')">Cancel</button>
                            </div>
                        </form>
                    </div>

                    <!-- Channels Tab -->
                    <div class="ss-tab" id="channels-tab">
                        <div class="ss-tab-header">
                            <h3>Channels</h3>
                            <button class="ss-btn ss-btn-primary" onclick="showCreateChannel()">
                                <i class="fas fa-plus"></i> Create Channel
                            </button>
                        </div>
                        <div class="ss-channel-list"></div>
                    </div>

                    <!-- Members Tab -->
                    <div class="ss-tab" id="members-tab">
                        <div class="ss-tab-header">
                            <h3>Members</h3>
                            <div class="ss-search-box">
                                <input type="text" id="members-search" class="ss-search-input" placeholder="Search members...">
                                <i class="fas fa-search ss-search-icon"></i>
                            </div>
                        </div>
                        <div class="ss-member-list"></div>
                    </div>

                    <!-- Roles Tab -->
                    <div class="ss-tab" id="roles-tab">
                        <div class="ss-tab-header">
                            <h3>Roles</h3>
                            <button class="ss-btn ss-btn-primary" onclick="showCreateRole()">
                                <i class="fas fa-plus"></i> Create Role
                            </button>
                        </div>
                        <div class="ss-roles-list"></div>
                    </div>

                    <!-- Delete Server Tab -->
                    <div class="ss-tab" id="delete-tab">
                        <div class="ss-delete-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <h3>Delete Server</h3>
                            <p>This action cannot be undone. This will permanently delete your server and remove all channels and messages.</p>
                            <div class="ss-confirmation-input">
                                <label>Please type <strong id="server-name-confirm"></strong> to confirm</label>
                                <input type="text" id="delete-confirmation" class="ss-input" />
                            </div>
                            <button class="ss-btn ss-btn-danger" id="delete-server-btn" disabled>
                                Delete Server
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="scripts/home.js"></script>
    <script src="scripts/server_settings.js"></script>
</body>
</html>