<?php
session_start();
require_once 'config.php';

// Check if installed
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit;
}

$config = include 'config.php';
if (!isset($config['installed']) || !$config['installed']) {
    header('Location: install.php');
    exit;
}

require_once 'includes/Database.php';
require_once 'includes/Security.php';

// Check authentication
if (!isset($_SESSION['user_id'])) {
    // Redirect to Discord OAuth
    header('Location: api/auth.php');
    exit;
}

$db = new Database($config);
$user = $db->getUserById($_SESSION['user_id']);

if (!$user) {
    header('Location: api/auth.php?logout=1');
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V3NTOM | eSport & Community Management</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #5865f2;
            --secondary-color: #7289da;
            --success-color: #57f287;
            --warning-color: #ffa500;
            --danger-color: #ed4245;
            --dark-bg: #1e1e1e;
            --darker-bg: #161616;
            --card-bg: #2f2f2f;
            --text-primary: #ffffff;
            --text-secondary: #b0b3b8;
            --border-color: #404040;
            --hover-bg: #3f3f3f;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--dark-bg) 0%, var(--darker-bg) 100%);
            color: var(--text-primary);
            min-height: 100vh;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: var(--card-bg);
            border-right: 1px solid var(--border-color);
            padding: 2rem 0;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        .logo {
            text-align: center;
            margin-bottom: 2rem;
            padding: 0 1.5rem;
        }

        .logo h2 {
            color: var(--primary-color);
            font-size: 1.5rem;
            font-weight: 700;
        }

        .logo p {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-item {
            margin: 0.5rem 1.5rem;
        }

        .nav-link {
            display: flex;
            align-items: center;
            padding: 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            background: var(--primary-color);
            color: white;
            transform: translateX(5px);
        }

        .nav-link i {
            width: 20px;
            margin-right: 1rem;
            font-size: 1.1rem;
        }

        .badge {
            background: var(--danger-color);
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 10px;
            margin-left: auto;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 280px;
            padding: 2rem;
            background: var(--dark-bg);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 2px solid var(--primary-color);
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: var(--card-bg);
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid var(--border-color);
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--success-color);
        }

        /* Content Sections */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .card {
            background: var(--card-bg);
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid var(--border-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .card h3 {
            color: var(--primary-color);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .stat-card {
            text-align: center;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: var(--success-color);
            margin: 1rem 0;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn-primary {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: var(--success-color);
            color: white;
        }

        .btn-warning {
            background: var(--warning-color);
            color: white;
        }

        .btn-danger {
            background: var(--danger-color);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .sidebar.mobile-open {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            color: var(--text-primary);
            font-size: 1.5rem;
            cursor: pointer;
        }

        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: block;
            }
        }

        .welcome-message {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            padding: 2rem;
            border-radius: 12px;
            color: white;
            margin-bottom: 2rem;
            text-align: center;
        }

        .welcome-message h2 {
            margin-bottom: 0.5rem;
        }

        .setup-card {
            background: var(--warning-color);
            color: white;
            padding: 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
        }

        .setup-card h3 {
            margin-bottom: 1rem;
        }

        .setup-steps {
            list-style: none;
        }

        .setup-steps li {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .setup-steps li i {
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <nav class="sidebar" id="sidebar">
            <div class="logo">
                <h2>V3NTOM</h2>
                <p>eSport & Community</p>
            </div>
            <ul class="nav-menu">
                <li class="nav-item">
                    <a href="#" class="nav-link active" data-section="dashboard">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="teams">
                        <i class="fas fa-shield-alt"></i>
                        Teams
                        <span class="badge">3</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="profile">
                        <i class="fas fa-user"></i>
                        Mein Profil
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" data-section="calendar">
                        <i class="fas fa-calendar-alt"></i>
                        Events
                    </a>
                </li>
                <?php if($user['role_id'] <= 3): // Admin/Manager ?>
                <li class="nav-item">
                    <a href="admin/" class="nav-link">
                        <i class="fas fa-cog"></i>
                        Admin Panel
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a href="api/auth.php?logout=1" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <main class="main-content">
            <header class="header">
                <div>
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 id="pageTitle">Dashboard</h1>
                </div>
                <div class="user-info">
                    <div class="status-indicator">
                        <div class="status-dot"></div>
                        <span>Online</span>
                    </div>
                    <img src="<?php echo $user['avatar'] ? 'https://cdn.discordapp.com/avatars/'.$user['discord_id'].'/'.$user['avatar'].'.png' : 'https://via.placeholder.com/50'; ?>" 
                         alt="User Avatar" class="user-avatar">
                    <div>
                        <div><?php echo htmlspecialchars($user['username']); ?></div>
                        <div style="color: var(--text-secondary); font-size: 0.8rem;">
                            <?php echo htmlspecialchars($user['status']); ?>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dashboard Section -->
            <section class="content-section active" id="dashboard">
                <div class="welcome-message">
                    <h2>Willkommen zurück, <?php echo htmlspecialchars($user['username']); ?>! 🎮</h2>
                    <p>Verwalte deine E-Sport Community mit dem V3NTOM Management System.</p>
                </div>

                <?php if($user['role_id'] <= 3): // Show setup card for admins ?>
                <div class="setup-card">
                    <h3><i class="fas fa-rocket"></i> Setup vervollständigen</h3>
                    <p>Vervollständige die Einrichtung deines E-Sport Management Systems:</p>
                    <ul class="setup-steps">
                        <li><i class="fas fa-check"></i> Framework installiert</li>
                        <li><i class="fas fa-check"></i> Admin-Account erstellt</li>
                        <li><i class="fas fa-times"></i> Discord Bot konfigurieren</li>
                        <li><i class="fas fa-times"></i> Teams erstellen</li>
                        <li><i class="fas fa-times"></i> Rechtesystem konfigurieren</li>
                    </ul>
                    <a href="admin/" class="btn btn-primary" style="margin-top: 1rem;">
                        <i class="fas fa-cog"></i> Zum Admin Panel
                    </a>
                </div>
                <?php endif; ?>

                <div class="dashboard-grid">
                    <div class="card stat-card">
                        <h3><i class="fas fa-users"></i> Community</h3>
                        <div class="stat-number"><?php echo $db->fetchOne($db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'"))['count']; ?></div>
                        <div class="stat-label">Aktive Mitglieder</div>
                    </div>
                    <div class="card stat-card">
                        <h3><i class="fas fa-shield-alt"></i> Teams</h3>
                        <div class="stat-number"><?php echo $db->fetchOne($db->query("SELECT COUNT(*) as count FROM teams"))['count']; ?></div>
                        <div class="stat-label">E-Sport Teams</div>
                    </div>
                    <div class="card stat-card">
                        <h3><i class="fas fa-calendar"></i> Events</h3>
                        <div class="stat-number"><?php echo $db->fetchOne($db->query("SELECT COUNT(*) as count FROM events WHERE start_date >= NOW()"))['count']; ?></div>
                        <div class="stat-label">Kommende Events</div>
                    </div>
                    <div class="card stat-card">
                        <h3><i class="fas fa-trophy"></i> Erfolge</h3>
                        <div class="stat-number">12</div>
                        <div class="stat-label">Turniersiege</div>
                    </div>
                </div>

                <div class="dashboard-grid">
                    <div class="card">
                        <h3><i class="fas fa-bullhorn"></i> Aktuelle Ankündigungen</h3>
                        <div style="padding: 1rem; background: var(--primary-color); border-radius: 8px; margin-bottom: 1rem;">
                            <strong>Willkommen zum V3NTOM Framework!</strong><br>
                            Das E-Sport Management System ist erfolgreich installiert. Besuche das Admin-Panel für weitere Konfiguration.
                        </div>
                        <div style="padding: 1rem; background: var(--darker-bg); border-radius: 8px;">
                            <strong>Discord Integration</strong><br>
                            Konfiguriere deinen Discord Bot für automatische Verwaltung.
                        </div>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-gamepad"></i> Verfügbare Teams</h3>
                        <?php
                        $teams = $db->fetchAll($db->query("SELECT * FROM teams WHERE recruitment_open = 1 LIMIT 3"));
                        if($teams):
                            foreach($teams as $team):
                        ?>
                        <div style="padding: 1rem; background: var(--darker-bg); border-radius: 8px; margin-bottom: 1rem;">
                            <strong><?php echo htmlspecialchars($team['name']); ?></strong><br>
                            <small style="color: var(--text-secondary);"><?php echo htmlspecialchars($team['game']); ?></small><br>
                            <button class="btn btn-primary btn-small" style="margin-top: 0.5rem;">
                                <i class="fas fa-user-plus"></i> Bewerben
                            </button>
                        </div>
                        <?php endforeach; else: ?>
                        <p style="color: var(--text-secondary);">Noch keine Teams verfügbar.</p>
                        <a href="admin/" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Erstes Team erstellen
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Teams Section -->
            <section class="content-section" id="teams">
                <div class="card">
                    <h3><i class="fas fa-shield-alt"></i> E-Sport Teams</h3>
                    <p>Hier findest du alle verfügbaren Teams und kannst dich bewerben.</p>
                    
                    <?php if(empty($teams)): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-users" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <h3>Noch keine Teams erstellt</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Teams werden vom Admin-Team erstellt und verwaltet.</p>
                        <?php if($user['role_id'] <= 3): ?>
                        <a href="admin/" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Erstes Team erstellen
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- Profile Section -->
            <section class="content-section" id="profile">
                <div class="dashboard-grid">
                    <div class="card">
                        <h3><i class="fas fa-user"></i> Mein Profil</h3>
                        <div style="text-align: center; margin-bottom: 2rem;">
                            <img src="<?php echo $user['avatar'] ? 'https://cdn.discordapp.com/avatars/'.$user['discord_id'].'/'.$user['avatar'].'.png' : 'https://via.placeholder.com/100'; ?>" 
                                 style="width: 100px; height: 100px; border-radius: 50%; border: 3px solid var(--primary-color);">
                            <h3 style="margin: 1rem 0;"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <span class="badge" style="background: var(--primary-color); padding: 0.5rem 1rem; border-radius: 20px;">
                                <?php echo htmlspecialchars($user['status']); ?>
                            </span>
                        </div>
                        
                        <div style="background: var(--darker-bg); padding: 1rem; border-radius: 8px;">
                            <strong>Discord ID:</strong> <?php echo htmlspecialchars($user['discord_id']); ?><br>
                            <strong>Mitglied seit:</strong> <?php echo date('d.m.Y', strtotime($user['created_at'])); ?><br>
                            <strong>Letzter Login:</strong> <?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Nie'; ?>
                        </div>
                    </div>

                    <div class="card">
                        <h3><i class="fas fa-chart-bar"></i> Meine Statistiken</h3>
                        <div class="stat-card">
                            <div style="font-size: 2rem; color: var(--success-color);"><?php echo $user['voice_time']; ?></div>
                            <div class="stat-label">Voice-Minuten</div>
                        </div>
                        <div class="stat-card">
                            <div style="font-size: 2rem; color: var(--primary-color);"><?php echo $user['message_count']; ?></div>
                            <div class="stat-label">Nachrichten</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Calendar Section -->
            <section class="content-section" id="calendar">
                <div class="card">
                    <h3><i class="fas fa-calendar-alt"></i> Kommende Events</h3>
                    <p>Hier findest du alle anstehenden Trainings, Matches und Community-Events.</p>
                    
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-calendar" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem;"></i>
                        <h3>Noch keine Events geplant</h3>
                        <p style="color: var(--text-secondary); margin-bottom: 2rem;">Events werden vom Team-Management erstellt.</p>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        // Navigation
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                if (!link.getAttribute('href') || link.getAttribute('href') === '#') {
                    e.preventDefault();
                    
                    // Remove active class from all links and sections
                    document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                    
                    // Add active class to clicked link
                    link.classList.add('active');
                    
                    // Show corresponding section
                    const sectionId = link.getAttribute('data-section');
                    if (sectionId) {
                        document.getElementById(sectionId).classList.add('active');
                        
                        // Update page title
                        const title = link.textContent.trim();
                        document.getElementById('pageTitle').textContent = title;
                    }
                }
            });
        });

        // Mobile menu toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', () => {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        });

        // Initialize
        console.log('V3NTOM Framework loaded successfully');
    </script>
</body>
</html>
