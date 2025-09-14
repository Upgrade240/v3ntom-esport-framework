<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

Security::checkAuth('admin.settings');
$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);

// Get statistics
$stats = $db->getStatistics();

// Discord Integration Statistics
$discordStats = [
    'discord_roles' => 0,
    'synced_members' => 0,
    'last_sync' => null,
    'bot_status' => 'unknown'
];

// Check if Discord tables exist and get stats
try {
    $discordRolesResult = $db->query("SELECT COUNT(*) as count FROM discord_roles");
    if ($discordRolesResult) {
        $discordStats['discord_roles'] = $db->fetchOne($discordRolesResult)['count'];
    }
} catch (Exception $e) {
    // Discord tables not yet created
}

try {
    $syncedMembersResult = $db->query("SELECT COUNT(DISTINCT user_id) as count FROM user_discord_roles");
    if ($syncedMembersResult) {
        $discordStats['synced_members'] = $db->fetchOne($syncedMembersResult)['count'];
    }
} catch (Exception $e) {
    // Discord tables not yet created
}

try {
    $lastSyncResult = $db->query("SELECT MAX(completed_at) as last_sync FROM discord_sync_log WHERE status = 'completed'");
    if ($lastSyncResult) {
        $lastSync = $db->fetchOne($lastSyncResult);
        $discordStats['last_sync'] = $lastSync['last_sync'];
    }
} catch (Exception $e) {
    // Discord tables not yet created
}

// Recent activity with Discord integration
$recentActivity = $db->fetchAll($db->query("
    SELECT 'user_joined' as type, u.username, u.created_at as timestamp, 'Neuer Benutzer registriert' as description
    FROM users u 
    WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    UNION ALL
    
    SELECT 'team_created' as type, u.username, t.created_at as timestamp, CONCAT('Team \"', t.name, '\" erstellt') as description
    FROM teams t
    LEFT JOIN users u ON t.leader_id = u.id
    WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    
    ORDER BY timestamp DESC 
    LIMIT 10
"));

// System info
$systemInfo = [
    'php_version' => PHP_VERSION,
    'mysql_version' => $db->fetchOne($db->query("SELECT VERSION() as version"))['version'],
    'disk_usage' => function_exists('disk_free_space') ? disk_free_space('.') : 'N/A'
];
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - V3NTOM Admin</title>
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
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--dark);
            color: var(--text);
            min-height: 100vh;
        }
        
        .header {
            background: var(--darker);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .nav {
            background: var(--card-bg);
            padding: 1rem 2rem;
            border-bottom: 1px solid var(--border);
        }
        
        .nav a {
            color: var(--text);
            text-decoration: none;
            margin-right: 2rem;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background 0.3s;
        }
        
        .nav a:hover, .nav a.active {
            background: var(--primary);
        }
        
        .container {
            padding: 2rem;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
        }
        
        .stat-icon.users { background: rgba(88, 101, 242, 0.2); color: var(--primary); }
        .stat-icon.teams { background: rgba(87, 242, 135, 0.2); color: var(--success); }
        .stat-icon.events { background: rgba(255, 165, 0, 0.2); color: var(--warning); }
        .stat-icon.tickets { background: rgba(237, 66, 69, 0.2); color: var(--danger); }
        .stat-icon.discord { background: rgba(114, 137, 218, 0.2); color: #7289da; }
        
        .stat-info h3 {
            font-size: 2rem;
            font-weight: bold;
            color: var(--text);
            margin-bottom: 0.25rem;
        }
        
        .stat-info p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .stat-info small {
            display: block;
            margin-top: 0.25rem;
            font-size: 0.8rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--border);
            margin-bottom: 1.5rem;
        }
        
        .card h3 {
            margin-bottom: 1rem;
            color: var(--text);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
        }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-content strong {
            color: var(--text);
        }
        
        .activity-content .description {
            color: var(--text-muted);
            font-size: 0.9rem;
        }
        
        .activity-time {
            color: var(--text-muted);
            font-size: 0.8rem;
        }
        
        .system-info {
            background: var(--darker);
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .system-info h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
        }
        
        .system-info .info-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
        }
        
        .system-info .info-item:last-child {
            margin-bottom: 0;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .action-btn {
            display: block;
            padding: 0.75rem;
            background: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 6px;
            text-align: center;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        .action-btn:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }
        
        .action-btn.danger {
            background: var(--danger);
        }
        
        .action-btn.success {
            background: var(--success);
            color: black;
        }

        .action-btn.discord {
            background: #7289da;
        }

        .discord-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success);
        }

        .status-dot.offline {
            background: var(--danger);
        }

        .discord-quick-sync {
            background: var(--darker);
            border-radius: 6px;
            padding: 1rem;
            margin-top: 1rem;
        }

        .sync-button {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        .sync-status {
            font-size: 0.8rem;
            color: var(--text-muted);
            text-align: center;
        }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-info {
            background: rgba(88, 101, 242, 0.1);
            border: 1px solid var(--primary);
            color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-tachometer-alt"></i> V3NTOM Admin Dashboard</h1>
        <div class="user-info">
            <span>Willkommen, Admin</span>
            <a href="../api/auth.php?logout" style="color: var(--danger);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
    
    <nav class="nav">
        <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a>
        <a href="users.php"><i class="fas fa-users"></i> Benutzer</a>
        <a href="teams.php"><i class="fas fa-users-cog"></i> Teams</a>
        <a href="discord-management.php"><i class="fab fa-discord"></i> Discord</a>
        <a href="moderation.php"><i class="fas fa-shield-alt"></i> Moderation</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Einstellungen</a>
    </nav>
    
    <div class="container">
        <!-- Statistics Cards -->
        <div class="dashboard-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_users']) ?></h3>
                    <p>Registrierte Benutzer</p>
                    <small style="color: var(--success);"><?= $stats['new_users_week'] ?> neue diese Woche</small>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon teams">
                    <i class="fas fa-users-cog"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['total_teams']) ?></h3>
                    <p>E-Sport Teams</p>
                    <small style="color: var(--success);">Teams registriert</small>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon discord">
                    <i class="fab fa-discord"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($discordStats['discord_roles']) ?></h3>
                    <p>Discord Rollen</p>
                    <small style="color: #7289da;"><?= number_format($discordStats['synced_members']) ?> synchronisierte Mitglieder</small>
                    <div class="discord-status">
                        <div class="status-dot <?= $discordStats['bot_status'] === 'online' ? '' : 'offline' ?>"></div>
                        <span style="font-size: 0.8rem;">Integration aktiv</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon events">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['upcoming_events']) ?></h3>
                    <p>Kommende Events</p>
                    <small style="color: var(--warning);"><?= $stats['pending_applications'] ?> offene Bewerbungen</small>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon tickets">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <div class="stat-info">
                    <h3><?= number_format($stats['open_tickets']) ?></h3>
                    <p>Offene Tickets</p>
                    <small style="color: var(--text-muted);">Support-System</small>
                </div>
            </div>
        </div>

        <?php if ($discordStats['discord_roles'] == 0 && $discordStats['synced_members'] == 0): ?>
        <!-- Discord Setup Alert -->
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i>
            <div>
                <strong>Discord-Integration einrichten:</strong> 
                Synchronisieren Sie Discord-Rollen und Mitglieder für die vollständige Integration.
                <a href="discord-management.php" style="color: var(--primary); margin-left: 0.5rem;">
                    <i class="fas fa-arrow-right"></i> Jetzt einrichten
                </a>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Activity -->
            <div class="card">
                <h3><i class="fas fa-clock"></i> Letzte Aktivitäten</h3>
                <div class="activity-list">
                    <?php if (empty($recentActivity)): ?>
                        <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                            <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                            Keine aktuellen Aktivitäten
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivity as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-icon">
                                    <i class="fas fa-<?= $activity['type'] === 'user_joined' ? 'user-plus' : 'users' ?>"></i>
                                </div>
                                <div class="activity-content">
                                    <strong><?= htmlspecialchars($activity['username'] ?? 'System') ?></strong>
                                    <div class="description"><?= htmlspecialchars($activity['description']) ?></div>
                                </div>
                                <div class="activity-time">
                                    <?= date('d.m.Y H:i', strtotime($activity['timestamp'])) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Info & Quick Actions -->
            <div>
                <div class="card">
                    <h3><i class="fas fa-server"></i> System-Information</h3>
                    <div class="system-info">
                        <h4>Server</h4>
                        <div class="info-item">
                            <span>PHP Version:</span>
                            <span><?= $systemInfo['php_version'] ?></span>
                        </div>
                        <div class="info-item">
                            <span>MySQL Version:</span>
                            <span><?= htmlspecialchars(substr($systemInfo['mysql_version'], 0, 10)) ?></span>
                        </div>
                        <div class="info-item">
                            <span>Freier Speicher:</span>
                            <span><?= is_numeric($systemInfo['disk_usage']) ? round($systemInfo['disk_usage'] / 1024 / 1024 / 1024, 1) . ' GB' : 'N/A' ?></span>
                        </div>
                        <?php if ($discordStats['last_sync']): ?>
                        <div class="info-item">
                            <span>Letzte Discord-Sync:</span>
                            <span><?= date('d.m H:i', strtotime($discordStats['last_sync'])) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="quick-actions">
                        <a href="users.php" class="action-btn">
                            <i class="fas fa-users"></i> Benutzer verwalten
                        </a>
                        <a href="teams.php" class="action-btn success">
                            <i class="fas fa-plus"></i> Team erstellen
                        </a>
                        <a href="discord-management.php" class="action-btn discord">
                            <i class="fab fa-discord"></i> Discord verwalten
                        </a>
                        <a href="moderation.php" class="action-btn danger">
                            <i class="fas fa-shield-alt"></i> Moderation
                        </a>
                    </div>
                </div>

                <!-- Discord Quick Actions -->
                <div class="card">
                    <h3><i class="fab fa-discord"></i> Discord Quick-Sync</h3>
                    <div class="discord-quick-sync">
                        <button class="action-btn sync-button discord" onclick="quickSyncDiscord()">
                            <i class="fas fa-sync-alt"></i> Schnell-Synchronisation
                        </button>
                        <div class="sync-status" id="syncStatus">
                            Bereit für Synchronisation
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        async function quickSyncDiscord() {
            const button = document.querySelector('.sync-button');
            const status = document.getElementById('syncStatus');
            
            // Button deaktivieren und Loading anzeigen
            button.disabled = true;
            button.innerHTML = '<div style="display: inline-block; width: 12px; height: 12px; border: 2px solid #fff; border-radius: 50%; border-top-color: transparent; animation: spin 1s linear infinite;"></div> Synchronisiere...';
            status.textContent = 'Synchronisation läuft...';
            
            try {
                const response = await fetch('/api/discord.php?action=sync-roles', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    status.textContent = data.success ? 'Synchronisation erfolgreich!' : 'Fehler bei Synchronisation';
                    status.style.color = data.success ? 'var(--success)' : 'var(--danger)';
                    
                    if (data.success) {
                        // Seite nach 2 Sekunden neu laden um Statistiken zu aktualisieren
                        setTimeout(() => window.location.reload(), 2000);
                    }
                } else {
                    throw new Error('Netzwerkfehler');
                }
            } catch (error) {
                status.textContent = 'Fehler: ' + error.message;
                status.style.color = 'var(--danger)';
            } finally {
                // Button wieder aktivieren
                setTimeout(() => {
                    button.disabled = false;
                    button.innerHTML = '<i class="fas fa-sync-alt"></i> Schnell-Synchronisation';
                    if (!status.textContent.includes('erfolgreich')) {
                        status.textContent = 'Bereit für Synchronisation';
                        status.style.color = 'var(--text-muted)';
                    }
                }, 3000);
            }
        }

        // CSS Animation für Loading Spinner
        const style = document.createElement('style');
        style.textContent = '@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
        document.head.appendChild(style);
    </script>
</body>
</html>