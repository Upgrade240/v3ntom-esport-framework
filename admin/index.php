<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

// Check authentication and admin permissions
Security::checkAuth('admin.settings');

$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);

$user = $db->getUserById($_SESSION['user_id']);
if (!$user || !$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
    header('Location: ../index.php');
    exit;
}

// Get system statistics
$stats = $db->getStatistics();
$recentActivity = $db->getRecentActivity(10);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V3NTOM Admin Panel</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary: #5865f2;
            --success: #57f287;
            --danger: #ed4245;
            --warning: #ffa500;
            --dark: #2f3136;
            --darker: #202225;
            --card-bg: #36393f;
            --text: #dcddde;
            --text-muted: #72767d;
            --border: #40444b;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--darker);
            color: var(--text);
            line-height: 1.6;
        }

        .admin-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .admin-header {
            background: linear-gradient(135deg, var(--primary), #7289da);
            color: white;
            padding: 2rem;
            border-radius: 12px;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }

        .admin-header p {
            opacity: 0.9;
            font-size: 1.1rem;
        }

        .admin-nav {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .nav-btn {
            background: var(--card-bg);
            color: var(--text);
            padding: 1rem 1.5rem;
            border: none;
            border-radius: 8px;
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid var(--border);
        }

        .nav-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-2px);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 2rem;
            border-radius: 12px;
            border: 1px solid var(--border);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border);
        }

        .card h3 {
            color: var(--primary);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 1rem;
            border-bottom: 1px solid var(--border);
            transition: background 0.3s ease;
        }

        .activity-item:hover {
            background: var(--darker);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.2rem;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: var(--text-muted);
            font-size: 0.8rem;
        }

        .btn {
            background: var(--primary);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn:hover {
            background: #4752c4;
            transform: translateY(-2px);
        }

        .btn-success { background: var(--success); }
        .btn-warning { background: var(--warning); }
        .btn-danger { background: var(--danger); }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid;
        }

        .alert-info {
            background: rgba(88, 101, 242, 0.1);
            border-color: var(--primary);
            color: var(--primary);
        }

        .alert-warning {
            background: rgba(255, 165, 0, 0.1);
            border-color: var(--warning);
            color: var(--warning);
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 2rem;
        }

        .action-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-card:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
        }

        .action-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
        }

        .system-status {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .status-item {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .status-online { background: var(--success); }
        .status-warning { background: var(--warning); }
        .status-offline { background: var(--danger); }

        @media (max-width: 768px) {
            .admin-container {
                padding: 1rem;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .admin-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <header class="admin-header">
            <div>
                <h1><i class="fas fa-crown"></i> Admin Panel</h1>
                <p>V3NTOM E-Sport Management System</p>
            </div>
            <div>
                <div style="text-align: right;">
                    <div style="font-size: 1.1rem; font-weight: bold;"><?php echo htmlspecialchars($user['username']); ?></div>
                    <div style="opacity: 0.8;">Administrator</div>
                </div>
            </div>
        </header>

        <?php if (!$config['discord']['token'] || $config['discord']['token'] === 'YOUR_BOT_TOKEN'): ?>
        <div class="alert alert-warning">
            <strong><i class="fas fa-exclamation-triangle"></i> Konfiguration unvollständig:</strong>
            Discord Bot Token muss noch konfiguriert werden. Besuche die Server-Einstellungen.
        </div>
        <?php endif; ?>

        <nav class="admin-nav">
            <a href="index.php" class="nav-btn">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
            <a href="permissions.php" class="nav-btn">
                <i class="fas fa-shield-alt"></i> Rechteverwaltung
            </a>
            <a href="documentation.php" class="nav-btn">
                <i class="fas fa-book"></i> Dokumentation
            </a>
            <a href="servers.php" class="nav-btn">
                <i class="fas fa-server"></i> Server-Config
            </a>
            <a href="../index.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Zurück zum Panel
            </a>
        </nav>

        <div class="system-status">
            <div class="status-item">
                <div class="status-indicator status-online"></div>
                <div>
                    <strong>Datenbank</strong><br>
                    <small>Verbunden</small>
                </div>
            </div>
            <div class="status-item">
                <div class="status-indicator <?php echo !empty($config['discord']['token']) && $config['discord']['token'] !== 'YOUR_BOT_TOKEN' ? 'status-online' : 'status-warning'; ?>"></div>
                <div>
                    <strong>Discord Bot</strong><br>
                    <small><?php echo !empty($config['discord']['token']) && $config['discord']['token'] !== 'YOUR_BOT_TOKEN' ? 'Konfiguriert' : 'Nicht konfiguriert'; ?></small>
                </div>
            </div>
            <div class="status-item">
                <div class="status-indicator <?php echo !empty($config['teamspeak']['host']) ? 'status-online' : 'status-offline'; ?>"></div>
                <div>
                    <strong>TeamSpeak</strong><br>
                    <small><?php echo !empty($config['teamspeak']['host']) ? 'Konfiguriert' : 'Nicht konfiguriert'; ?></small>
                </div>
            </div>
            <div class="status-item">
                <div class="status-indicator status-online"></div>
                <div>
                    <strong>Framework</strong><br>
                    <small>v1.0.0</small>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--primary);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number" style="color: var(--primary);"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Aktive Mitglieder</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success);">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-number" style="color: var(--success);"><?php echo $stats['new_users_week']; ?></div>
                <div class="stat-label">Neue Mitglieder (7 Tage)</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--warning);">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="stat-number" style="color: var(--warning);"><?php echo $stats['total_teams']; ?></div>
                <div class="stat-label">Teams</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="color: var(--danger);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-number" style="color: var(--danger);"><?php echo $stats['pending_applications']; ?></div>
                <div class="stat-label">Offene Bewerbungen</div>
            </div>
        </div>

        <div class="content-grid">
            <div class="card">
                <h3><i class="fas fa-chart-line"></i> Neueste Aktivitäten</h3>
                
                <?php if (empty($recentActivity)): ?>
                    <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                        <i class="fas fa-history" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Noch keine Aktivitäten vorhanden.</p>
                        <p>Aktivitäten werden hier angezeigt, sobald Aktionen durchgeführt werden.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: var(--primary);">
                                <i class="fas fa-<?php 
                                    echo $activity['action_type'] === 'warn' ? 'exclamation-triangle' :
                                         ($activity['action_type'] === 'kick' ? 'user-times' :
                                          ($activity['action_type'] === 'ban' ? 'ban' : 'cog')); 
                                ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-text">
                                    <strong><?php echo htmlspecialchars($activity['moderator_username']); ?></strong>
                                    hat <strong><?php echo htmlspecialchars($activity['target_username']); ?></strong>
                                    <?php echo $activity['action_type'] === 'warn' ? 'verwarnt' : 
                                              ($activity['action_type'] === 'kick' ? 'gekickt' : 
                                               ($activity['action_type'] === 'ban' ? 'gebannt' : 'bearbeitet')); ?>
                                </div>
                                <div class="activity-time"><?php echo date('d.m.Y H:i', strtotime($activity['created_at'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div>
                <div class="card" style="margin-bottom: 1rem;">
                    <h3><i class="fas fa-rocket"></i> Schnellstart</h3>
                    
                    <div style="margin-bottom: 1rem;">
                        <h4 style="color: var(--warning); margin-bottom: 0.5rem;">🔧 Setup vervollständigen:</h4>
                        <ul style="list-style: none; padding-left: 0;">
                            <li style="margin: 0.5rem 0;">
                                <i class="fas fa-check" style="color: var(--success);"></i>
                                Framework installiert
                            </li>
                            <li style="margin: 0.5rem 0;">
                                <i class="fas fa-check" style="color: var(--success);"></i>
                                Admin-Account erstellt
                            </li>
                            <li style="margin: 0.5rem 0;">
                                <i class="fas fa-<?php echo !empty($config['discord']['token']) && $config['discord']['token'] !== 'YOUR_BOT_TOKEN' ? 'check' : 'times'; ?>" 
                                   style="color: var(--<?php echo !empty($config['discord']['token']) && $config['discord']['token'] !== 'YOUR_BOT_TOKEN' ? 'success' : 'danger'; ?>);"></i>
                                Discord Bot konfigurieren
                            </li>
                            <li style="margin: 0.5rem 0;">
                                <i class="fas fa-<?php echo $stats['total_teams'] > 0 ? 'check' : 'times'; ?>" 
                                   style="color: var(--<?php echo $stats['total_teams'] > 0 ? 'success' : 'danger'; ?>);"></i>
                                Erstes Team erstellen
                            </li>
                            <li style="margin: 0.5rem 0;">
                                <i class="fas fa-times" style="color: var(--danger);"></i>
                                Rechtesystem konfigurieren
                            </li>
                        </ul>
                    </div>

                    <a href="documentation.php" class="btn">
                        <i class="fas fa-book"></i> Vollständige Anleitung
                    </a>
                </div>

                <div class="card">
                    <h3><i class="fas fa-exclamation-triangle"></i> System-Hinweise</h3>
                    
                    <?php if (file_exists('../install.php')): ?>
                    <div style="background: rgba(237, 66, 69, 0.1); border: 1px solid var(--danger); border-radius: 6px; padding: 1rem; margin-bottom: 1rem;">
                        <strong style="color: var(--danger);">Sicherheitswarnung:</strong><br>
                        Die <code>install.php</code> Datei existiert noch. Lösche sie aus Sicherheitsgründen!
                    </div>
                    <?php endif; ?>

                    <div style="background: rgba(88, 101, 242, 0.1); border: 1px solid var(--primary); border-radius: 6px; padding: 1rem;">
                        <strong style="color: var(--primary);">Tipp:</strong><br>
                        Besuche die Dokumentation für detaillierte Anleitungen zur Konfiguration.
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <h3><i class="fas fa-tools"></i> Schnellaktionen</h3>
            
            <div class="quick-actions">
                <div class="action-card" onclick="location.href='permissions.php'">
                    <div class="action-icon"><i class="fas fa-shield-alt"></i></div>
                    <div><strong>Rollen verwalten</strong></div>
                    <small>Berechtigungen konfigurieren</small>
                </div>

                <div class="action-card" onclick="location.href='servers.php'">
                    <div class="action-icon"><i class="fas fa-server"></i></div>
                    <div><strong>Server Setup</strong></div>
                    <small>Discord & TeamSpeak</small>
                </div>

                <div class="action-card" onclick="createTeam()">
                    <div class="action-icon"><i class="fas fa-plus"></i></div>
                    <div><strong>Team erstellen</strong></div>
                    <small>Neues E-Sport Team</small>
                </div>

                <div class="action-card" onclick="location.href='documentation.php'">
                    <div class="action-icon"><i class="fas fa-book"></i></div>
                    <div><strong>Dokumentation</strong></div>
                    <small>Vollständige Anleitung</small>
                </div>

                <div class="action-card" onclick="viewLogs()">
                    <div class="action-icon"><i class="fas fa-list"></i></div>
                    <div><strong>System-Logs</strong></div>
                    <small>Aktivitäten anzeigen</small>
                </div>

                <div class="action-card" onclick="manageUsers()">
                    <div class="action-icon"><i class="fas fa-users"></i></div>
                    <div><strong>Benutzer</strong></div>
                    <small>Mitglieder verwalten</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        function createTeam() {
            const teamName = prompt('Team Name:');
            if (teamName) {
                fetch('../api/teams.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'create',
                        name: teamName,
                        game: prompt('Spiel (z.B. CS2, Valorant):') || '',
                        description: prompt('Beschreibung:') || ''
                    })
                }).then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Team erfolgreich erstellt!');
                        location.reload();
                    } else {
                        alert('Fehler: ' + data.error);
                    }
                });
            }
        }

        function viewLogs() {
            alert('System-Logs Feature wird in einer zukünftigen Version verfügbar sein.');
        }

        function manageUsers() {
            location.href = '../admin/users.php';
        }

        // Auto-refresh statistics every 30 seconds
        setInterval(() => {
            fetch('api/stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update statistics without page reload
                        console.log('Statistics updated');
                    }
                })
                .catch(error => console.log('Stats update failed'));
        }, 30000);

        console.log('V3NTOM Admin Panel loaded successfully');
    </script>
</body>
</html>
