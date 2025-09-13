<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

Security::checkAuth('members.warn');
$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);

$message = '';
$error = '';

// Handle moderation actions
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'warn_user':
            $userId = (int)$_POST['user_id'];
            $reason = Security::sanitizeInput($_POST['reason']);
            
            if ($permissions->canManageUser($_SESSION['user_id'], $userId)) {
                $db->insert('moderation_logs', [
                    'action_type' => 'warn',
                    'target_user_id' => $userId,
                    'moderator_id' => $_SESSION['user_id'],
                    'reason' => $reason,
                    'platform' => 'panel',
                    'severity' => $_POST['severity'] ?? 'medium',
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]);
                $message = 'Verwarnung erfolgreich erteilt';
            } else {
                $error = 'Keine Berechtigung diesen Benutzer zu verwalten';
            }
            break;
            
        case 'ban_user':
            $userId = (int)$_POST['user_id'];
            $reason = Security::sanitizeInput($_POST['reason']);
            $duration = (int)$_POST['duration'] * 3600; // Convert hours to seconds
            
            if ($permissions->canManageUser($_SESSION['user_id'], $userId)) {
                // Update user status
                $db->update('users', ['status' => 'banned'], 'id = ?', [$userId]);
                
                // Log moderation action
                $db->insert('moderation_logs', [
                    'action_type' => 'ban',
                    'target_user_id' => $userId,
                    'moderator_id' => $_SESSION['user_id'],
                    'reason' => $reason,
                    'platform' => 'panel',
                    'duration' => $duration > 0 ? $duration : null,
                    'severity' => 'high',
                    'expires_at' => $duration > 0 ? date('Y-m-d H:i:s', time() + $duration) : null,
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]);
                $message = 'Benutzer erfolgreich gebannt';
            } else {
                $error = 'Keine Berechtigung diesen Benutzer zu bannen';
            }
            break;
            
        case 'unban_user':
            $userId = (int)$_POST['user_id'];
            $reason = Security::sanitizeInput($_POST['reason']);
            
            if ($permissions->canManageUser($_SESSION['user_id'], $userId)) {
                // Update user status
                $db->update('users', ['status' => 'active'], 'id = ?', [$userId]);
                
                // Log moderation action
                $db->insert('moderation_logs', [
                    'action_type' => 'unban',
                    'target_user_id' => $userId,
                    'moderator_id' => $_SESSION['user_id'],
                    'reason' => $reason,
                    'platform' => 'panel',
                    'severity' => 'medium',
                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                    'user_agent' => $_SERVER['HTTP_USER_AGENT']
                ]);
                $message = 'Benutzer erfolgreich entbannt';
            } else {
                $error = 'Keine Berechtigung diesen Benutzer zu entbannen';
            }
            break;
    }
}

// Get recent moderation logs
$moderationLogs = $db->fetchAll("
    SELECT ml.*, 
           target.username as target_username,
           target.discord_id as target_discord_id,
           target.status as target_status,
           mod.username as moderator_username
    FROM moderation_logs ml
    JOIN users target ON ml.target_user_id = target.id
    JOIN users mod ON ml.moderator_id = mod.id
    ORDER BY ml.created_at DESC
    LIMIT 50
");

// Get users for moderation (active and banned)
$users = $db->fetchAll("
    SELECT u.*, r.display_name as role_name, r.color as role_color
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    ORDER BY u.username
");

// Get banned users
$bannedUsers = $db->fetchAll("
    SELECT u.*, r.display_name as role_name, r.color as role_color
    FROM users u 
    LEFT JOIN roles r ON u.role_id = r.id 
    WHERE u.status = 'banned'
    ORDER BY u.username
");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderation - V3NTOM Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #2f3136;
            color: #dcddde;
            min-height: 100vh;
        }
        
        .header {
            background: #202225;
            padding: 1rem 2rem;
            border-bottom: 1px solid #40444b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .nav {
            background: #36393f;
            padding: 1rem 2rem;
        }
        
        .nav a {
            color: #dcddde;
            text-decoration: none;
            margin-right: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav a:hover, .nav a.active {
            background: #5865f2;
        }
        
        .container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .card {
            background: #36393f;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #40444b;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: rgba(87, 242, 135, 0.1);
            border: 1px solid #57f287;
            color: #57f287;
        }
        
        .alert-danger {
            background: rgba(237, 66, 69, 0.1);
            border: 1px solid #ed4245;
            color: #ed4245;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .action-card {
            background: #2f3136;
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }
        
        .action-card:hover {
            border-color: #5865f2;
            transform: translateY(-2px);
        }
        
        .action-card.warn { border-left: 4px solid #ffa500; }
        .action-card.ban { border-left: 4px solid #ed4245; }
        .action-card.unban { border-left: 4px solid #57f287; }
        .action-card.logs { border-left: 4px solid #5865f2; }
        
        .action-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .action-card.warn .action-icon { color: #ffa500; }
        .action-card.ban .action-icon { color: #ed4245; }
        .action-card.unban .action-icon { color: #57f287; }
        .action-card.logs .action-icon { color: #5865f2; }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-input, .form-select, .form-textarea {
            width: 100%;
            padding: 0.75rem;
            background: #40444b;
            border: 1px solid #72767d;
            border-radius: 4px;
            color: #dcddde;
            font-size: 1rem;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            margin: 0.25rem;
        }
        
        .btn-primary { background: #5865f2; color: white; }
        .btn-warning { background: #ffa500; color: black; }
        .btn-danger { background: #ed4245; color: white; }
        .btn-success { background: #57f287; color: black; }
        
        .btn:hover { opacity: 0.8; }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
        }
        
        .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #36393f;
            border-radius: 8px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .close {
            background: none;
            border: none;
            color: #dcddde;
            font-size: 1.5rem;
            cursor: pointer;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            background: #2f3136;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .logs-table th, .logs-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #40444b;
        }
        
        .logs-table th {
            background: #202225;
            font-weight: 600;
        }
        
        .action-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .action-warn { background: #ffa500; color: black; }
        .action-ban { background: #ed4245; color: white; }
        .action-unban { background: #57f287; color: black; }
        .action-kick { background: #ff6b6b; color: white; }
        .action-note { background: #5865f2; color: white; }
        
        .severity-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .severity-low { background: #57f287; color: black; }
        .severity-medium { background: #ffa500; color: black; }
        .severity-high { background: #ed4245; color: white; }
        .severity-critical { background: #8b0000; color: white; }
        
        .search-box {
            margin-bottom: 1rem;
            position: relative;
        }
        
        .search-input {
            width: 100%;
            padding: 0.75rem 2.5rem 0.75rem 1rem;
            background: #40444b;
            border: 1px solid #72767d;
            border-radius: 4px;
            color: #dcddde;
        }
        
        .search-icon {
            position: absolute;
            right: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #72767d;
        }
        
        .banned-users-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .banned-user-card {
            background: #2f3136;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #ed4245;
        }
        
        .banned-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .banned-user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #5865f2;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>V3NTOM Admin Panel</h1>
        <a href="../api/auth.php?logout" style="color: #ed4245;">Logout</a>
    </div>
    
    <nav class="nav">
        <a href="dashboard.php">Dashboard</a>
        <a href="users.php">Benutzer</a>
        <a href="teams.php">Teams</a>
        <a href="moderation.php" class="active">Moderation</a>
        <a href="settings.php">Einstellungen</a>
    </nav>
    
    <div class="container">
        <div class="card">
            <h2><i class="fas fa-shield-alt"></i> Moderation & Audit Tools</h2>
            <p>Verwalte Benutzer, überwache Aktivitäten und führe Moderationsmaßnahmen durch.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div class="quick-actions">
            <div class="action-card warn" onclick="openModal('warnModal')">
                <i class="fas fa-exclamation-triangle action-icon"></i>
                <h3>Benutzer verwarnen</h3>
                <p>Erteile eine offizielle Verwarnung</p>
            </div>
            
            <div class="action-card ban" onclick="openModal('banModal')">
                <i class="fas fa-ban action-icon"></i>
                <h3>Benutzer bannen</h3>
                <p>Temporärer oder permanenter Bann</p>
            </div>
            
            <div class="action-card unban" onclick="openModal('unbanModal')">
                <i class="fas fa-check-circle action-icon"></i>
                <h3>Benutzer entbannen</h3>
                <p>Bann aufheben und reaktivieren</p>
            </div>
            
            <div class="action-card logs" onclick="scrollToLogs()">
                <i class="fas fa-history action-icon"></i>
                <h3>Moderation Logs</h3>
                <p>Alle Moderationsaktivitäten anzeigen</p>
            </div>
        </div>
        
        <!-- Banned Users -->
        <?php if (!empty($bannedUsers)): ?>
            <div class="card">
                <h3><i class="fas fa-ban"></i> Gebannte Benutzer (<?= count($bannedUsers) ?>)</h3>
                <div class="banned-users-grid">
                    <?php foreach ($bannedUsers as $user): ?>
                        <div class="banned-user-card">
                            <div class="banned-user-info">
                                <div class="banned-user-avatar">
                                    <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                    <small style="color: #72767d;">ID: <?= $user['discord_id'] ?></small>
                                </div>
                            </div>
                            <?php if ($permissions->canManageUser($_SESSION['user_id'], $user['id'])): ?>
                                <button class="btn btn-success btn-sm" 
                                        onclick="quickUnban(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>')">
                                    <i class="fas fa-unlock"></i> Entbannen
                                </button>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Moderation Logs -->
        <div class="card" id="moderationLogs">
            <h3><i class="fas fa-history"></i> Aktuelle Moderation Logs</h3>
            
            <div class="search-box">
                <input type="text" class="search-input" placeholder="Nach Benutzer oder Grund suchen..." 
                       onkeyup="filterLogs(this.value)">
                <i class="fas fa-search search-icon"></i>
            </div>
            
            <div style="overflow-x: auto;">
                <table class="logs-table">
                    <thead>
                        <tr>
                            <th>Datum</th>
                            <th>Aktion</th>
                            <th>Betroffener Benutzer</th>
                            <th>Moderator</th>
                            <th>Grund</th>
                            <th>Schwere</th>
                        </tr>
                    </thead>
                    <tbody id="logsTableBody">
                        <?php if (empty($moderationLogs)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem; color: #72767d;">
                                    <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                                    Keine Moderationsaktivitäten gefunden
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($moderationLogs as $log): ?>
                                <tr class="log-row" data-search="<?= strtolower($log['target_username'] . ' ' . $log['moderator_username'] . ' ' . $log['reason']) ?>">
                                    <td><?= date('d.m.Y H:i', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <span class="action-badge action-<?= $log['action_type'] ?>">
                                            <?= ucfirst($log['action_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($log['target_username']) ?></strong><br>
                                        <small style="color: #72767d;"><?= $log['target_discord_id'] ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($log['moderator_username']) ?></td>
                                    <td><?= htmlspecialchars($log['reason'] ?? 'Kein Grund angegeben') ?></td>
                                    <td>
                                        <span class="severity-badge severity-<?= $log['severity'] ?>">
                                            <?= ucfirst($log['severity']) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Warn User Modal -->
    <div id="warnModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Benutzer verwarnen</h3>
                <button class="close" onclick="closeModal('warnModal')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="warn_user">
                
                <div class="form-group">
                    <label class="form-label">Benutzer auswählen</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Benutzer wählen...</option>
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['status'] !== 'banned' && $permissions->canManageUser($_SESSION['user_id'], $user['id'])): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Schweregrad</label>
                    <select name="severity" class="form-select" required>
                        <option value="low">Niedrig</option>
                        <option value="medium" selected>Mittel</option>
                        <option value="high">Hoch</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Grund</label>
                    <textarea name="reason" class="form-textarea" 
                              placeholder="Grund für die Verwarnung..." required></textarea>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeModal('warnModal')">Abbrechen</button>
                    <button type="submit" class="btn btn-warning">Verwarnung erteilen</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Ban User Modal -->
    <div id="banModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-ban"></i> Benutzer bannen</h3>
                <button class="close" onclick="closeModal('banModal')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="ban_user">
                
                <div class="form-group">
                    <label class="form-label">Benutzer auswählen</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Benutzer wählen...</option>
                        <?php foreach ($users as $user): ?>
                            <?php if ($user['status'] !== 'banned' && $permissions->canManageUser($_SESSION['user_id'], $user['id'])): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Dauer (Stunden)</label>
                    <select name="duration" class="form-select">
                        <option value="0">Permanent</option>
                        <option value="1">1 Stunde</option>
                        <option value="6">6 Stunden</option>
                        <option value="24">24 Stunden</option>
                        <option value="168">7 Tage</option>
                        <option value="720">30 Tage</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Grund</label>
                    <textarea name="reason" class="form-textarea" 
                              placeholder="Grund für den Bann..." required></textarea>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeModal('banModal')">Abbrechen</button>
                    <button type="submit" class="btn btn-danger">Benutzer bannen</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Unban User Modal -->
    <div id="unbanModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-check-circle"></i> Benutzer entbannen</h3>
                <button class="close" onclick="closeModal('unbanModal')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="unban_user">
                
                <div class="form-group">
                    <label class="form-label">Gebannten Benutzer auswählen</label>
                    <select name="user_id" class="form-select" required>
                        <option value="">Benutzer wählen...</option>
                        <?php foreach ($bannedUsers as $user): ?>
                            <?php if ($permissions->canManageUser($_SESSION['user_id'], $user['id'])): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Grund für Entbannung</label>
                    <textarea name="reason" class="form-textarea" 
                              placeholder="Grund für die Entbannung..." required></textarea>
                </div>
                
                <div style="text-align: right;">
                    <button type="button" class="btn" onclick="closeModal('unbanModal')">Abbrechen</button>
                    <button type="submit" class="btn btn-success">Benutzer entbannen</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function scrollToLogs() {
            document.getElementById('moderationLogs').scrollIntoView({
                behavior: 'smooth'
            });
        }
        
        function filterLogs(searchTerm) {
            const rows = document.querySelectorAll('.log-row');
            const term = searchTerm.toLowerCase();
            
            rows.forEach(row => {
                const searchData = row.getAttribute('data-search');
                if (searchData.includes(term)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function quickUnban(userId, username) {
            if (confirm(`Möchtest du ${username} wirklich entbannen?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="unban_user">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="reason" value="Schnell-Entbannung über Admin-Panel">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>