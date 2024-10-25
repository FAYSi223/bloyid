let currentServer = null;
let currentChannel = null;
let messagePollingInterval = null;

document.getElementById('create-server-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    fetch('php/create_server.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            location.reload();
        } else {
            alert(data.message);
        }
    })
    .catch(error => console.error('Error:', error));
});

function hideModal() {
    document.getElementById('create-server-modal').style.display = 'none';
}

function showCreateServer() {
    document.getElementById('create-server-modal').style.display = 'flex';
}

function switchServer(serverId) {
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    currentServer = serverId;
    
    document.querySelectorAll('.server-icon').forEach(icon => {
        icon.classList.remove('active');
    });
    
    const clickedServer = document.querySelector(`.server-icon[data-server-id="${serverId}"]`);
    if (clickedServer) {
        clickedServer.classList.add('active');
    }
    
    fetch(`php/get_server_details.php?server_id=${serverId}`)
        .then(response => response.json())
        .then(data => {
            document.getElementById('current-server').textContent = data.server.name;
            
            if (data.server.banner_url) {
                document.querySelector('.main-content').style.backgroundImage = `url(${data.server.banner_url})`;
            }
            
            const channelsList = document.getElementById('channels-list');
            channelsList.innerHTML = '';
            
            data.channels.forEach(channel => {
                const channelDiv = document.createElement('div');
                channelDiv.className = 'channel-item';
                channelDiv.setAttribute('data-channel-id', channel.id);
                channelDiv.innerHTML = `
                    <i class="fas fa-hashtag"></i>
                    <span>${channel.name}</span>
                `;
                channelDiv.onclick = () => switchChannel(channel.id);
                channelsList.appendChild(channelDiv);
            });
            
            const membersList = document.querySelector('.friends-sidebar');
            if (membersList && data.members) {
                membersList.innerHTML = '<h3>Members</h3>';
                
                const onlineMembers = data.members.filter(m => m.status === 'online');
                const offlineMembers = data.members.filter(m => m.status === 'offline');
                
                membersList.innerHTML += `<div class="members-category">Online - ${onlineMembers.length}</div>`;
                onlineMembers.forEach(member => {
                    membersList.innerHTML += `
                        <div class="member-item">
                            <div class="user-avatar"></div>
                            <div>
                                <div class="member-name">${member.username}</div>
                                <div class="member-role">${member.role}</div>
                            </div>
                            <div class="status-indicator online"></div>
                        </div>
                    `;
                });
                
                membersList.innerHTML += `<div class="members-category">Offline - ${offlineMembers.length}</div>`;
                offlineMembers.forEach(member => {
                    membersList.innerHTML += `
                        <div class="member-item">
                            <div class="user-avatar"></div>
                            <div>
                                <div class="member-name">${member.username}</div>
                                <div class="member-role">${member.role}</div>
                            </div>
                            <div class="status-indicator offline"></div>
                        </div>
                    `;
                });
            }
            
            if (data.channels.length > 0) {
                switchChannel(data.channels[0].id);
            }
        })
        .catch(error => console.error('Error fetching server details:', error));
}

function switchChannel(channelId) {
    currentChannel = channelId;
    
    if (messagePollingInterval) {
        clearInterval(messagePollingInterval);
    }
    
    document.querySelectorAll('.channel-item').forEach(channel => {
        channel.classList.remove('active');
    });
    
    const selectedChannel = document.querySelector(`.channel-item[data-channel-id="${channelId}"]`);
    if (selectedChannel) {
        selectedChannel.classList.add('active');
    }
    
    loadMessages();
    messagePollingInterval = setInterval(loadMessages, 3000);
}

function loadMessages() {
    if (!currentChannel) return;
    
    fetch(`php/get_channel_messages.php?channel_id=${currentChannel}`)
        .then(response => response.json())
        .then(data => {
            const chatArea = document.getElementById('chat-area');
            chatArea.innerHTML = data.messages.map(message => `
                <div class="message">
                    <div class="message-header">
                        <div class="message-username">${message.username}</div>
                        <div class="message-timestamp">${message.timestamp}</div>
                    </div>
                    <div class="message-content">${message.content}</div>
                </div>
            `).join('');
            
            chatArea.scrollTop = chatArea.scrollHeight;
        })
        .catch(error => console.error('Error loading messages:', error));
}

document.getElementById('message-input').addEventListener('keypress', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const content = this.value.trim();
        
        if (content && currentChannel) {
            sendMessage(content);
            this.value = '';
        }
    }
});

document.getElementById('message-input').addEventListener('input', function() {
    this.style.height = 'auto';
    this.style.height = (this.scrollHeight) + 'px';
});

function sendMessage(content) {
    fetch('php/send_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            channel_id: currentChannel,
            content: content
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            loadMessages();
        } else {
            console.error('Error sending message:', data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    const firstServer = document.querySelector('.server-icon:not(.create-server)');
    if (firstServer) {
        firstServer.click();
    }
});

function showServerSettings() {
    if (!currentServer) return;

    fetch(`php/get_server.php?id=${currentServer}`)
        .then(response => response.json())
        .then(server => {
            const form = document.getElementById('server-settings-form');
            form.server_name.value = server.name;
            form.description.value = server.description;

            if (server.icon_url) {
                document.querySelector('.icon-preview').style.backgroundImage = `url(${server.icon_url})`;
            }
            if (server.banner_url) {
                document.querySelector('.banner-preview').style.backgroundImage = `url(${server.banner_url})`;
            }
        });

    document.getElementById('server-settings-modal').style.display = 'block';
}

document.getElementById('server-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('server_id', currentServer);
    
    fetch('php/update_server.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showSuccessMessage('Server settings updated successfully');
            loadServerData(currentServer);
        }
    });
});

document.getElementById('create-channel-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    formData.append('server_id', currentServer);
    
    fetch('php/create_channel.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            hideModal('create-channel-modal');
            showSuccessMessage('Channel created successfully');
            switchServer(currentServer);
        }
    });
});

document.querySelectorAll('.settings-nav-item').forEach(item => {
    item.addEventListener('click', function() {
        document.querySelectorAll('.settings-nav-item').forEach(i => i.classList.remove('active'));
        document.querySelectorAll('.settings-tab').forEach(t => t.classList.remove('active'));
        
        this.classList.add('active');
        document.getElementById(`${this.dataset.tab}-tab`).classList.add('active');
    });
});
