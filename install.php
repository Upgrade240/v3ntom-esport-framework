<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if already installed
if (file_exists('config.php') && !isset($_GET['force'])) {
    $config = include 'config.php';
    if (isset($config['installed']) && $config['installed'] === true) {
        header('Location: index.php');
        exit;
    }
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$errors = [];
$success = [];

// Installation steps
switch ($step) {
    case 1:
        // System requirements check
        $requirements = [
            'PHP >= 7.4' => version_compare(PHP_VERSION, '7.4.0') >= 0,
            'MySQLi Extension' => extension_loaded('mysqli'),
            'cURL Extension' => extension_loaded('curl'),
            'JSON Extension' => extension_loaded('json'),
            'OpenSSL Extension' => extension_loaded('openssl'),
            'Config writable' => is_writable('.') || is_writable('config.php')
        ];
        break;
        
    case 2:
        // Database configuration
        if ($_POST) {
            $db_host = $_POST['db_host'] ?? 'localhost';
            $db_name = $_POST['db_name'] ?? 'v3ntom_esport';
            $db_user = $_POST['db_user'] ?? 'root';
            $db_pass = $_POST['db_pass'] ?? '';
            
            // Test database connection
            $mysqli = new mysqli($db_host, $db_user, $db_pass);
            if ($mysqli->connect_error) {
                $errors[] = "Database connection failed: " . $mysqli->connect_error;
            } else {
                // Create database if not exists
                $mysqli->query("CREATE DATABASE IF NOT EXISTS `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $mysqli->select_db($db_name);
                
                // Store DB config in session
                $_SESSION['db_config'] = [
                    'host' => $db_host,
                    'name' => $db_name,
                    'user' => $db_user,
                    'pass' => $db_pass
                ];
                
                if (empty($errors)) {
                    header('Location: install.php?step=3');
                    exit;
                }
            }
        }
        break;
        
    case 3:
        // Discord Bot configuration
        if ($_POST) {
            $discord_token = $_POST['discord_token'] ?? '';
            $discord_client_id = $_POST['discord_client_id'] ?? '';
            $discord_client_secret = $_POST['discord_client_secret'] ?? '';
            $discord_guild_id = $_POST['discord_guild_id'] ?? '';
            
            if (empty($discord_token) || empty($discord_client_id) || empty($discord_client_secret)) {
                $errors[] = "Alle Discord-Felder sind erforderlich";
            } else {
                $_SESSION['discord_config'] = [
                    'token' => $discord_token,
                    'client_id' => $discord_client_id,
                    'client_secret' => $discord_client_secret,
                    'guild_id' => $discord_guild_id
                ];
                
                header('Location: install.php?step=4');
                exit;
            }
        }
        break;
        
    case 4:
        // TeamSpeak configuration (optional)
        if ($_POST) {
            $_SESSION['teamspeak_config'] = [
                'host' => $_POST['ts_host'] ?? '',
                'port' => $_POST['ts_port'] ?? '10011',
                'user' => $_POST['ts_user'] ?? '',
                'pass' => $_POST['ts_pass'] ?? '',
                'server_id' => $_POST['ts_server_id'] ?? '1'
            ];
            
            header('Location: install.php?step=5');
            exit;
        }
        break;
        
    case 5:
        // Admin account creation
        if ($_POST) {
            $admin_discord_id = $_POST['admin_discord_id'] ?? '';
            $admin_username = $_POST['admin_username'] ?? 'Admin';
            
            if (empty($admin_discord_id)) {
                $errors[] = "Discord ID ist erforderlich";
            } else {
                $_SESSION['admin_config'] = [
                    'discord_id' => $admin_discord_id,
                    'username' => $admin_username
                ];
                
                header('Location: install.php?step=6');
                exit;
            }
        }
        break;
        
    case 6:
        // Final installation
        try {
            // Create database tables
            $db = $_SESSION['db_config'];
            $mysqli = new mysqli($db['host'], $db['user'], $db['pass'], $db['name']);
            
            $sql = file_get_contents('sql/install.sql');
            $queries = explode(';', $sql);
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query)) {
                    if (!$mysqli->query($query)) {
                        throw new Exception("SQL Error: " . $mysqli->error);
                    }
                }
            }
            
            // Create admin user
            $admin = $_SESSION['admin_config'];
            $stmt = $mysqli->prepare("INSERT INTO users (discord_id, username, role_id, created_at) VALUES (?, ?, 1, NOW())");
            $stmt->bind_param("ss", $admin['discord_id'], $admin['username']);
            $stmt->execute();
            
            // Create config.php
            $config_content = generateConfigFile();
            file_put_contents('config.php', $config_content);
            
            // Clear session
            session_destroy();
            
            $success[] = "Installation erfolgreich abgeschlossen!";
            
        } catch (Exception $e) {
            $errors[] = "Installation Error: " . $e->getMessage();
        }
        break;
}

function generateConfigFile() {
    $db = $_SESSION['db_config'];
    $discord = $_SESSION['discord_config'];
    $teamspeak = $_SESSION['teamspeak_config'];
    
    return "<?php
return [
    'installed' => true,
    'base_url' => 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']),
    
    // Database Configuration
    'database' => [
        'host' => '{$db['host']}',
        'name' => '{$db['name']}',
        'user' => '{$db['user']}',
        'pass' => '{$db['pass']}',
        'charset' => 'utf8mb4'
    ],
    
    // Discord Configuration
    'discord' => [
        'token' => '{$discord['token']}',
        'client_id' => '{$discord['client_id']}',
        'client_secret' => '{$discord['client_secret']}',
        'guild_id' => '{$discord['guild_id']}',
        'redirect_uri' => 'http://' . \$_SERVER['HTTP_HOST'] . dirname(\$_SERVER['SCRIPT_NAME']) . '/api/auth.php'
    ],
    
    // TeamSpeak Configuration
    'teamspeak' => [
        'host' => '{$teamspeak['host']}',
        'port' => '{$teamspeak['port']}',
        'user' => '{$teamspeak['user']}',
        'pass' => '{$teamspeak['pass']}',
        'server_id' => '{$teamspeak['server_id']}'
    ],
    
    // Security
    'session_timeout' => 7200, // 2 hours
    'csrf_protection' => true,
    
    // Features
    'features' => [
        'discord_integration' => true,
        'teamspeak_integration' => " . (!empty($teamspeak['host']) ? 'true' : 'false') . ",
        'team_applications' => true,
        'event_calendar' => true,
        'statistics' => true
    ]
];
?>";
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V3NTOM Installation</title>
    <style>
        :root {
            --primary: #5865f2;
            --success: #57f287;
            --danger: #ed4245;
            --warning: #ffa500;
            --dark: #2f3136;
            --darker: #202225;
            --light: #ffffff;
            --text: #dcddde;
        }
        
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            color: var(--text);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .installer {
            background: var(--dark);
            border-radius: 12px;
            padding: 3rem;
            max-width: 600px;
            width: 90%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .header h1 {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #404040;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .step.active { background: var(--primary); }
        .step.completed { background: var(--success); }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--light);
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 0.75rem;
            background: var(--darker);
            border: 1px solid #404040;
            border-radius: 6px;
            color: var(--text);
            font-size: 1rem;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: var(--primary);
            color: white;
        }
        
        .btn-primary:hover {
            background: #4752c4;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
        }
        
        .alert-success { background: rgba(87, 242, 135, 0.1); border: 1px solid var(--success); }
        .alert-danger { background: rgba(237, 66, 69, 0.1); border: 1px solid var(--danger); }
        
        .requirements {
            list-style: none;
        }
        
        .requirements li {
            padding: 0.5rem 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .req-status {
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        
        .req-ok { background: var(--success); color: white; }
        .req-error { background: var(--danger); color: white; }
    </style>
</head>
<body>
    <div class="installer">
        <div class="header">
            <h1>V3NTOM Installation</h1>
            <p>E-Sport & Community Management System</p>
        </div>
        
        <div class="step-indicator">
            <?php for ($i = 1; $i <= 6; $i++): ?>
                <div class="step <?php echo $i < $step ? 'completed' : ($i == $step ? 'active' : ''); ?>">
                    <?php echo $i; ?>
                </div>
            <?php endfor; ?>
        </div>
        
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
        
        <?php foreach ($success as $msg): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($msg); ?></div>
        <?php endforeach; ?>
        
        <?php if ($step == 1): ?>
            <h2>System Requirements</h2>
            <ul class="requirements">
                <?php foreach ($requirements as $req => $status): ?>
                    <li>
                        <?php echo $req; ?>
                        <span class="req-status <?php echo $status ? 'req-ok' : 'req-error'; ?>">
                            <?php echo $status ? 'OK' : 'ERROR'; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
            
            <?php if (array_product($requirements)): ?>
                <a href="install.php?step=2" class="btn btn-primary">Weiter zur Datenbank</a>
            <?php else: ?>
                <p style="color: var(--danger); margin-top: 1rem;">
                    Bitte erfülle alle Systemanforderungen bevor du fortfährst.
                </p>
            <?php endif; ?>
            
        <?php elseif ($step == 2): ?>
            <h2>Datenbank Konfiguration</h2>
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Database Host</label>
                    <input type="text" name="db_host" class="form-input" value="localhost" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Database Name</label>
                    <input type="text" name="db_name" class="form-input" value="v3ntom_esport" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Database User</label>
                    <input type="text" name="db_user" class="form-input" value="root" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Database Password</label>
                    <input type="password" name="db_pass" class="form-input">
                </div>
                
                <button type="submit" class="btn btn-primary">Datenbank testen & weiter</button>
            </form>
            
        <?php elseif ($step == 3): ?>
            <h2>Discord Bot Konfiguration</h2>
            <p style="margin-bottom: 1rem; color: var(--text);">
                Erstelle eine Discord Application auf <a href="https://discord.com/developers/applications" target="_blank" style="color: var(--primary);">Discord Developer Portal</a>
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Bot Token</label>
                    <input type="password" name="discord_token" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Client ID</label>
                    <input type="text" name="discord_client_id" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Client Secret</label>
                    <input type="password" name="discord_client_secret" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Guild ID (Server ID)</label>
                    <input type="text" name="discord_guild_id" class="form-input" placeholder="Optional">
                </div>
                
                <button type="submit" class="btn btn-primary">Discord konfigurieren & weiter</button>
            </form>
            
        <?php elseif ($step == 4): ?>
            <h2>TeamSpeak Konfiguration (Optional)</h2>
            <p style="margin-bottom: 1rem; color: var(--text);">
                Für TeamSpeak Integration benötigst du Query-Login Daten.
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">TeamSpeak Server IP</label>
                    <input type="text" name="ts_host" class="form-input" placeholder="ts.example.com">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Query Port</label>
                    <input type="text" name="ts_port" class="form-input" value="10011">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Query Username</label>
                    <input type="text" name="ts_user" class="form-input" placeholder="serveradmin">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Query Password</label>
                    <input type="password" name="ts_pass" class="form-input">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Virtual Server ID</label>
                    <input type="text" name="ts_server_id" class="form-input" value="1">
                </div>
                
                <button type="submit" class="btn btn-primary">TeamSpeak konfigurieren & weiter</button>
            </form>
            
        <?php elseif ($step == 5): ?>
            <h2>Admin Account erstellen</h2>
            <p style="margin-bottom: 1rem; color: var(--text);">
                Erstelle deinen Administrator-Account. Du benötigst deine Discord User ID.
            </p>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">Deine Discord User ID</label>
                    <input type="text" name="admin_discord_id" class="form-input" required>
                    <small style="color: var(--text); font-size: 0.8rem;">
                        Rechtsklick auf deinen Namen in Discord → "Benutzer-ID kopieren"
                    </small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Admin Username</label>
                    <input type="text" name="admin_username" class="form-input" value="Admin">
                </div>
                
                <button type="submit" class="btn btn-primary">Admin erstellen & Installation abschließen</button>
            </form>
            
        <?php elseif ($step == 6): ?>
            <?php if (empty($errors)): ?>
                <h2>Installation erfolgreich!</h2>
                <p style="color: var(--success); margin-bottom: 2rem;">
                    Das V3NTOM E-Sport Management System wurde erfolgreich installiert!
                </p>
                
                <div style="background: rgba(88, 101, 242, 0.1); padding: 1rem; border-radius: 6px; margin-bottom: 1rem;">
                    <h3 style="color: var(--primary); margin-bottom: 0.5rem;">Nächste Schritte:</h3>
                    <ul style="margin-left: 1.5rem; color: var(--text);">
                        <li>Lösche die <code>install.php</code> Datei aus Sicherheitsgründen</li>
                        <li>Logge dich mit Discord ein</li>
                        <li>Gehe zum Admin-Panel für weitere Konfiguration</li>
                        <li>Lade deinen Discord Bot in deinen Server ein</li>
                    </ul>
                </div>
                
                <a href="index.php" class="btn btn-primary">Zum Management Panel</a>
                <a href="admin/" class="btn btn-primary" style="margin-left: 1rem;">Zum Admin Panel</a>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
