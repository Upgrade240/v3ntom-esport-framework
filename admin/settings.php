<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

Security::checkAuth('admin.settings');
$config = include '../config.php';
$db = new Database($config);

$message = '';
$error = '';

// Handle settings updates
if ($_POST && isset($_POST['save_settings'])) {
    try {
        $settings = $_POST['settings'];
        foreach ($settings as $key => $value) {
            $db->update('system_settings', ['value' => $value], '`key` = ?', [$key]);
        }
        $message = 'Einstellungen erfolgreich gespeichert';
    } catch (Exception $e) {
        $error = 'Fehler beim Speichern: ' . $e->getMessage();
    }
}

// Get current settings grouped by category
$allSettings = $db->fetchAll("SELECT * FROM system_settings ORDER BY category, `key`");
$settingsByCategory = [];
foreach ($allSettings as $setting) {
    $settingsByCategory[$setting['category']][] = $setting;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Einstellungen - V3NTOM Admin</title>
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
            max-width: 1200px;
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
        
        .settings-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }
        
        .settings-nav {
            background: #2f3136;
            border-radius: 8px;
            padding: 1rem;
            height: fit-content;
            position: sticky;
            top: 2rem;
        }
        
        .settings-nav h3 {
            color: #5865f2;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .nav-item {
            display: block;
            padding: 0.75rem 1rem;
            color: #dcddde;
            text-decoration: none;
            border-radius: 4px;
            margin-bottom: 0.5rem;
            transition: all 0.3s;
        }
        
        .nav-item:hover, .nav-item.active {
            background: #5865f2;
            color: white;
        }
        
        .nav-item i {
            margin-right: 0.5rem;
            width: 16px;
        }
        
        .settings-content {
            background: #36393f;
            border-radius: 8px;
            padding: 2rem;
            border: 1px solid #40444b;
        }
        
        .setting-section {
            margin-bottom: 2rem;
            display: none;
        }
        
        .setting-section.active {
            display: block;
        }
        
        .setting-section h2 {
            color: #5865f2;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .setting-section p {
            color: #72767d;
            margin-bottom: 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #dcddde;
        }
        
        .form-description {
            font-size: 0.9rem;
            color: #72767d;
            margin-bottom: 0.5rem;
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
        
        .form-input:focus, .form-select:focus, .form-textarea:focus {
            outline: none;
            border-color: #5865f2;
        }
        
        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .checkbox {
            width: 18px;
            height: 18px;
            accent-color: #5865f2;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 1rem;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary {
            background: #5865f2;
            color: white;
        }
        
        .btn-primary:hover {
            background: #4752c4;
        }
        
        .btn-success {
            background: #57f287;
            color: black;
        }
        
        .btn-danger {
            background: #ed4245;
            color: white;
        }
        
        .form-actions {
            margin-top: 2rem;
            text-align: right;
            border-top: 1px solid #40444b;
            padding-top: 1rem;
        }
        
        .test-connection {
            display: inline-block;
            margin-left: 0.5rem;
            padding: 0.5rem 1rem;
            background: #ffa500;
            color: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
        }
        
        @media (max-width: 768px) {
            .settings-grid {
                grid-template-columns: 1fr;
            }
            
            .settings-nav {
                position: static;
                margin-bottom: 2rem;
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
        <a href="moderation.php">Moderation</a>
        <a href="settings.php" class="active">Einstellungen</a>
    </nav>
    
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="settings-grid">
            <div class="settings-nav">
                <h3>Einstellungen</h3>
                <a href="#general" class="nav-item active" onclick="showSection('general')">
                    <i class="fas fa-cog"></i> Allgemein
                </a>
                <a href="#discord" class="nav-item" onclick="showSection('discord')">
                    <i class="fab fa-discord"></i> Discord
                </a>
                <a href="#teamspeak" class="nav-item" onclick="showSection('teamspeak')">
                    <i class="fas fa-headset"></i> TeamSpeak
                </a>
                <a href="#features" class="nav-item" onclick="showSection('features')">
                    <i class="fas fa-puzzle-piece"></i> Features
                </a>
                <a href="#security" class="nav-item" onclick="showSection('security')">
                    <i class="fas fa-shield-alt"></i> Sicherheit
                </a>
                <a href="#system" class="nav-item" onclick="showSection('system')">
                    <i class="fas fa-server"></i> System
                </a>
            </div>
            
            <form method="POST" class="settings-content">
                <!-- General Settings -->
                <div id="general" class="setting-section active">
                    <h2><i class="fas fa-cog"></i> Allgemeine Einstellungen</h2>
                    <p>Grundlegende Konfiguration deiner V3NTOM Installation.</p>
                    
                    <?php if (isset($settingsByCategory['general'])): ?>
                        <?php foreach ($settingsByCategory['general'] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $setting['key']))) ?></label>
                                <div class="form-description"><?= htmlspecialchars($setting['description']) ?></div>
                                
                                <?php if ($setting['type'] === 'boolean'): ?>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="settings[<?= $setting['key'] ?>]" 
                                               value="true" class="checkbox"
                                               <?= $setting['value'] === 'true' ? 'checked' : '' ?>>
                                        <span>Aktiviert</span>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="settings[<?= $setting['key'] ?>]" 
                                           value="<?= htmlspecialchars($setting['value']) ?>" 
                                           class="form-input">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Discord Settings -->
                <div id="discord" class="setting-section">
                    <h2><i class="fab fa-discord"></i> Discord Integration</h2>
                    <p>Konfiguriere die Discord Bot Integration und OAuth2 Einstellungen.</p>
                    
                    <div class="form-group">
                        <label class="form-label">Bot Token</label>
                        <div class="form-description">Der Bot Token aus dem Discord Developer Portal</div>
                        <input type="password" value="<?= htmlspecialchars($config['discord']['token']) ?>" 
                               class="form-input" readonly>
                        <small style="color: #72767d;">Wird aus der config.php gelesen</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Client ID</label>
                        <div class="form-description">Die Discord Application Client ID</div>
                        <input type="text" value="<?= htmlspecialchars($config['discord']['client_id']) ?>" 
                               class="form-input" readonly>
                        <small style="color: #72767d;">Wird aus der config.php gelesen</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Guild ID (Server ID)</label>
                        <div class="form-description">Die ID deines Discord Servers</div>
                        <input type="text" value="<?= htmlspecialchars($config['discord']['guild_id']) ?>" 
                               class="form-input" readonly>
                    </div>
                    
                    <?php if (isset($settingsByCategory['discord'])): ?>
                        <?php foreach ($settingsByCategory['discord'] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $setting['key']))) ?></label>
                                <div class="form-description"><?= htmlspecialchars($setting['description']) ?></div>
                                <input type="text" name="settings[<?= $setting['key'] ?>]" 
                                       value="<?= htmlspecialchars($setting['value']) ?>" 
                                       class="form-input">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- TeamSpeak Settings -->
                <div id="teamspeak" class="setting-section">
                    <h2><i class="fas fa-headset"></i> TeamSpeak Integration</h2>
                    <p>Konfiguriere die TeamSpeak Server Query Integration.</p>
                    
                    <div class="form-group">
                        <label class="form-label">Server Host</label>
                        <input type="text" value="<?= htmlspecialchars($config['teamspeak']['host']) ?>" 
                               class="form-input" readonly>
                        <small style="color: #72767d;">Wird aus der config.php gelesen</small>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Query Port</label>
                        <input type="text" value="<?= htmlspecialchars($config['teamspeak']['port']) ?>" 
                               class="form-input" readonly>
                    </div>
                    
                    <?php if (isset($settingsByCategory['teamspeak'])): ?>
                        <?php foreach ($settingsByCategory['teamspeak'] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $setting['key']))) ?></label>
                                <div class="form-description"><?= htmlspecialchars($setting['description']) ?></div>
                                <input type="text" name="settings[<?= $setting['key'] ?>]" 
                                       value="<?= htmlspecialchars($setting['value']) ?>" 
                                       class="form-input">
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Features Settings -->
                <div id="features" class="setting-section">
                    <h2><i class="fas fa-puzzle-piece"></i> Feature-Einstellungen</h2>
                    <p>Aktiviere oder deaktiviere verschiedene Features des Systems.</p>
                    
                    <?php if (isset($settingsByCategory['features'])): ?>
                        <?php foreach ($settingsByCategory['features'] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $setting['key']))) ?></label>
                                <div class="form-description"><?= htmlspecialchars($setting['description']) ?></div>
                                
                                <?php if ($setting['type'] === 'boolean'): ?>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="settings[<?= $setting['key'] ?>]" 
                                               value="true" class="checkbox"
                                               <?= $setting['value'] === 'true' ? 'checked' : '' ?>>
                                        <span>Aktiviert</span>
                                    </div>
                                <?php else: ?>
                                    <input type="text" name="settings[<?= $setting['key'] ?>]" 
                                           value="<?= htmlspecialchars($setting['value']) ?>" 
                                           class="form-input">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- Security Settings -->
                <div id="security" class="setting-section">
                    <h2><i class="fas fa-shield-alt"></i> Sicherheits-Einstellungen</h2>
                    <p>Konfiguriere Sicherheitsrichtlinien und Session-Management.</p>
                    
                    <?php if (isset($settingsByCategory['security'])): ?>
                        <?php foreach ($settingsByCategory['security'] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $setting['key']))) ?></label>
                                <div class="form-description"><?= htmlspecialchars($setting['description']) ?></div>
                                
                                <?php if ($setting['type'] === 'boolean'): ?>
                                    <div class="checkbox-group">
                                        <input type="checkbox" name="settings[<?= $setting['key'] ?>]" 
                                               value="true" class="checkbox"
                                               <?= $setting['value'] === 'true' ? 'checked' : '' ?>>
                                        <span>Aktiviert</span>
                                    </div>
                                <?php elseif ($setting['type'] === 'integer'): ?>
                                    <input type="number" name="settings[<?= $setting['key'] ?>]" 
                                           value="<?= htmlspecialchars($setting['value']) ?>" 
                                           class="form-input">
                                <?php else: ?>
                                    <input type="text" name="settings[<?= $setting['key'] ?>]" 
                                           value="<?= htmlspecialchars($setting['value']) ?>" 
                                           class="form-input">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <!-- System Settings -->
                <div id="system" class="setting-section">
                    <h2><i class="fas fa-server"></i> System-Einstellungen</h2>
                    <p>Erweiterte System-Konfiguration und Wartung.</p>
                    
                    <?php if (isset($settingsByCategory['system'])): ?>
                        <?php foreach ($settingsByCategory['system'] as $setting): ?>
                            <div class="form-group">
                                <label class="form-label"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $setting['key']))) ?></label>
                                <div class="form-description"><?= htmlspecialchars($setting['description']) ?></div>
                                
                                <?php if ($setting['type'] === 'integer'): ?>
                                    <input type="number" name="settings[<?= $setting['key'] ?>]" 
                                           value="<?= htmlspecialchars($setting['value']) ?>" 
                                           class="form-input">
                                <?php else: ?>
                                    <input type="text" name="settings[<?= $setting['key'] ?>]" 
                                           value="<?= htmlspecialchars($setting['value']) ?>" 
                                           class="form-input">
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label class="form-label">Cache leeren</label>
                        <div class="form-description">Leert alle System-Caches und Session-Daten</div>
                        <button type="button" class="btn btn-danger" onclick="clearCache()">
                            <i class="fas fa-trash"></i> Cache leeren
                        </button>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="fas fa-save"></i> Einstellungen speichern
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showSection(sectionId) {
            // Hide all sections
            document.querySelectorAll('.setting-section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Remove active class from nav items
            document.querySelectorAll('.nav-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
            
            // Add active class to clicked nav item
            document.querySelector(`[onclick="showSection('${sectionId}')"]`).classList.add('active');
        }
        
        function clearCache() {
            if (confirm('Möchtest du wirklich alle Caches leeren?')) {
                fetch('../api/system.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'clear_cache'
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Cache erfolgreich geleert');
                        location.reload();
                    } else {
                        alert('Fehler beim Leeren des Caches: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Fehler beim Leeren des Caches');
                });
            }
        }
    </script>
</body>
</html>