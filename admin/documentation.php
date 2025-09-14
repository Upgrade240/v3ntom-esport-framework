<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>V3NTOM E-Sport Framework - Dokumentation</title>
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: var(--dark);
            color: var(--text);
            line-height: 1.6;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin-bottom: 3rem;
            padding: 2rem;
            background: var(--card-bg);
            border-radius: 12px;
            border: 1px solid var(--border);
        }

        .header h1 {
            color: var(--primary);
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .header p {
            color: var(--text-muted);
            font-size: 1.2rem;
        }

        .nav {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 3rem;
        }

        .nav-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
            text-decoration: none;
            color: var(--text);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .nav-card:hover {
            border-color: var(--primary);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(88, 101, 242, 0.2);
        }

        .nav-card i {
            color: var(--primary);
            font-size: 2rem;
            margin-bottom: 1rem;
            display: block;
        }

        .nav-card h3 {
            color: var(--text);
            margin-bottom: 0.5rem;
        }

        .nav-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .section {
            display: none;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section h2 {
            color: var(--primary);
            border-bottom: 2px solid var(--primary);
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .code-block {
            background: #1e1e1e;
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            overflow-x: auto;
        }

        .code-block pre {
            color: #ce9178;
            font-family: 'Consolas', 'Monaco', monospace;
            white-space: pre-wrap;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 1rem 0;
            background: var(--darker);
            border-radius: 8px;
            overflow: hidden;
        }

        .table th,
        .table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        .table th {
            background: var(--darker);
            color: var(--primary);
            font-weight: 600;
        }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            border-left: 4px solid;
        }

        .alert-info {
            background: rgba(88, 101, 242, 0.1);
            border-left-color: var(--primary);
            color: var(--primary);
        }

        .alert-warning {
            background: rgba(255, 165, 0, 0.1);
            border-left-color: var(--warning);
            color: var(--warning);
        }

        .back-btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }

        .back-btn:hover {
            opacity: 0.8;
        }

        .method-badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.8rem;
            margin-right: 0.5rem;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0;
        }

        .feature-card {
            background: var(--darker);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 1.5rem;
        }

        .feature-card h4 {
            color: var(--primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        ul {
            padding-left: 1.5rem;
            margin: 1rem 0;
        }

        li {
            margin-bottom: 0.5rem;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .nav {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-gamepad"></i> V3NTOM Framework</h1>
            <p>Vollständige E-Sport Community Management System Dokumentation</p>
        </div>

        <!-- Navigation -->
        <div id="navigation" class="nav">
            <div class="nav-card" onclick="showSection('overview')">
                <i class="fas fa-home"></i>
                <h3>Übersicht</h3>
                <p>Framework-Features und Architektur</p>
            </div>
            <div class="nav-card" onclick="showSection('installation')">
                <i class="fas fa-download"></i>
                <h3>Installation</h3>
                <p>Setup-Anleitung und Konfiguration</p>
            </div>
            <div class="nav-card" onclick="showSection('database')">
                <i class="fas fa-database"></i>
                <h3>Database-Klasse</h3>
                <p>SQL-Operationen und Methoden</p>
            </div>
            <div class="nav-card" onclick="showSection('security')">
                <i class="fas fa-shield-alt"></i>
                <h3>Security</h3>
                <p>Authentifizierung und CSRF-Schutz</p>
            </div>
            <div class="nav-card" onclick="showSection('permissions')">
                <i class="fas fa-key"></i>
                <h3>Permissions</h3>
                <p>Rechtesystem und Rollen</p>
            </div>
            <div class="nav-card" onclick="showSection('apis')">
                <i class="fas fa-code"></i>
                <h3>API Endpunkte</h3>
                <p>REST APIs für alle Funktionen</p>
            </div>
            <div class="nav-card" onclick="showSection('admin')">
                <i class="fas fa-tachometer-alt"></i>
                <h3>Admin Interface</h3>
                <p>Dashboard und Verwaltung</p>
            </div>
            <div class="nav-card" onclick="showSection('integrations')">
                <i class="fab fa-discord"></i>
                <h3>Integrationen</h3>
                <p>Discord Bot und TeamSpeak</p>
            </div>
        </div>

        <!-- Übersicht Section -->
        <div id="overview" class="section active">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-rocket"></i> Framework Übersicht</h2>
            
            <div class="alert alert-info">
                <strong>V3NTOM E-Sport Framework</strong> - Ein vollständiges Community-Management-System mit Discord & TeamSpeak Integration.
            </div>

            <div class="feature-grid">
                <div class="feature-card">
                    <h4><i class="fas fa-users"></i> Benutzerverwaltung</h4>
                    <ul>
                        <li>Discord OAuth2 Login</li>
                        <li>Automatische Synchronisation</li>
                        <li>Hierarchisches Rechtesystem</li>
                        <li>Rollen-basierte Zugriffskontrolle</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-shield-alt"></i> Sicherheit</h4>
                    <ul>
                        <li>CSRF-Protection</li>
                        <li>SQL-Injection Schutz</li>
                        <li>XSS-Prevention</li>
                        <li>Session-Management</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-users-cog"></i> Team-Management</h4>
                    <ul>
                        <li>Team-Erstellung und Verwaltung</li>
                        <li>Bewerbungssystem</li>
                        <li>Automatische Genehmigung</li>
                        <li>Team-Statistiken</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-calendar"></i> Events</h4>
                    <ul>
                        <li>Event-Kalender</li>
                        <li>Training-Sessions</li>
                        <li>Match-Planung</li>
                        <li>Benachrichtigungen</li>
                    </ul>
                </div>
            </div>

            <h3>Technische Architektur</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Komponente</th>
                        <th>Technologie</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Backend</td>
                        <td>PHP 7.4+ OOP</td>
                        <td>Modular mit PSR-4 Autoloading</td>
                    </tr>
                    <tr>
                        <td>Database</td>
                        <td>MySQL/MariaDB</td>
                        <td>Optimierte Schemas mit Foreign Keys</td>
                    </tr>
                    <tr>
                        <td>Frontend</td>
                        <td>Vanilla JS + CSS3</td>
                        <td>Progressive Web App Features</td>
                    </tr>
                    <tr>
                        <td>API</td>
                        <td>RESTful JSON</td>
                        <td>Rate-Limited mit CORS Support</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Installation Section -->
        <div id="installation" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-download"></i> Installation</h2>
            
            <div class="alert alert-info">
                <strong>Systemanforderungen:</strong> PHP >= 7.4, MySQL >= 5.7, Apache/Nginx mit mod_rewrite
            </div>

            <h3>1. Repository herunterladen</h3>
            <div class="code-block">
                <pre># Repository klonen
git clone https://github.com/Upgrade240/v3ntom-esport-framework.git
cd v3ntom-esport-framework

# Auf Webserver kopieren
sudo cp -r * /var/www/html/v3ntom/
cd /var/www/html/v3ntom

# Berechtigungen setzen
sudo chown -R www-data:www-data .
sudo chmod -R 755 .
sudo chmod -R 775 logs/ uploads/</pre>
            </div>

            <h3>2. Setup-Wizard starten</h3>
            <p>Öffne deinen Browser und navigiere zu:</p>
            <div class="code-block">
                <pre>http://your-domain.com/v3ntom/install.php</pre>
            </div>

            <h3>3. Discord OAuth2 konfigurieren</h3>
            <ol>
                <li>Besuche das <a href="https://discord.com/developers/applications" target="_blank">Discord Developer Portal</a></li>
                <li>Erstelle eine neue Application</li>
                <li>Generiere Bot Token</li>
                <li>Konfiguriere OAuth2:</li>
            </ol>
            <div class="code-block">
                <pre>Redirect URI: http://your-domain.com/api/auth.php
Scopes: identify, email, guilds
Bot Permissions: Kick Members, Ban Members, Manage Roles</pre>
            </div>

            <div class="alert alert-warning">
                <strong>Wichtig:</strong> Lösche die install.php nach dem Setup!
            </div>
        </div>

        <!-- Database Section -->
        <div id="database" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-database"></i> Database-Klasse</h2>
            
            <p>Die Database-Klasse bietet sichere SQL-Operationen mit automatischen Prepared Statements.</p>

            <h3>Konstruktor</h3>
            <div class="code-block">
                <pre>public function __construct($config)</pre>
            </div>
            <p>Initialisiert die Datenbankverbindung mit der Konfiguration aus config.php.</p>

            <h3>Wichtige Methoden</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Methode</th>
                        <th>Parameter</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="method-badge">query()</span></td>
                        <td>$sql, $params = []</td>
                        <td>Führt SQL mit Prepared Statements aus</td>
                    </tr>
                    <tr>
                        <td><span class="method-badge">insert()</span></td>
                        <td>$table, $data</td>
                        <td>Fügt Datensatz ein, gibt ID zurück</td>
                    </tr>
                    <tr>
                        <td><span class="method-badge">update()</span></td>
                        <td>$table, $data, $where, $params</td>
                        <td>Aktualisiert Datensätze</td>
                    </tr>
                    <tr>
                        <td><span class="method-badge">fetchAll()</span></td>
                        <td>$result</td>
                        <td>Alle Ergebnisse als Array</td>
                    </tr>
                    <tr>
                        <td><span class="method-badge">fetchOne()</span></td>
                        <td>$result</td>
                        <td>Ein Ergebnis als Array</td>
                    </tr>
                </tbody>
            </table>

            <h3>Beispiel-Verwendung</h3>
            <div class="code-block">
                <pre>$config = include 'config.php';
$db = new Database($config);

// Benutzer abfragen
$result = $db->query("SELECT * FROM users WHERE status = ?", ['active']);
$users = $db->fetchAll($result);

// Neuen Benutzer einfügen
$userId = $db->insert('users', [
    'username' => 'MaxMustermann',
    'email' => 'max@example.com',
    'status' => 'active'
]);</pre>
            </div>
        </div>

        <!-- Security Section -->
        <div id="security" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-shield-alt"></i> Security-Klasse</h2>
            
            <p>Zentrale Sicherheitsklasse für Authentifizierung, CSRF-Schutz und Input-Validation.</p>

            <h3>Authentifizierung</h3>
            <div class="code-block">
                <pre>// Prüft ob Benutzer eingeloggt ist
Security::checkAuth();

// Prüft spezifische Berechtigung
Security::checkAuth('admin.users');</pre>
            </div>

            <h3>CSRF-Schutz</h3>
            <div class="code-block">
                <pre>// Token generieren
$token = Security::generateCSRFToken();

// Token validieren
if (Security::validateCSRFToken($_POST['csrf_token'])) {
    // Sicher zu verarbeiten
}</pre>
            </div>

            <h3>Input-Sanitization</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Methode</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="method-badge">sanitizeInput()</span></td>
                        <td>Bereinigt HTML/Script-Tags</td>
                    </tr>
                    <tr>
                        <td><span class="method-badge">validateEmail()</span></td>
                        <td>E-Mail Format validieren</td>
                    </tr>
                    <tr>
                        <td><span class="method-badge">hashPassword()</span></td>
                        <td>Passwort sicher hashen</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Permissions Section -->
        <div id="permissions" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-key"></i> PermissionManager</h2>
            
            <p>Hierarchisches Rechtesystem ähnlich TeamSpeak mit rollen-basierter Zugriffskontrolle.</p>

            <h3>Berechtigung prüfen</h3>
            <div class="code-block">
                <pre>$permissions = new PermissionManager($db);

if ($permissions->hasPermission($userId, 'teams.create')) {
    // Benutzer kann Teams erstellen
}

// Hierarchie-Check: Admin hat alle Rechte
if ($permissions->hasPermission($userId, 'admin.users')) {
    // Vollzugriff auf Benutzerverwaltung
}</pre>
            </div>

            <h3>Verfügbare Berechtigungen</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Kategorie</th>
                        <th>Berechtigung</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td rowspan="4"><strong>Admin</strong></td>
                        <td>admin.users</td>
                        <td>Benutzerverwaltung</td>
                    </tr>
                    <tr>
                        <td>admin.settings</td>
                        <td>Systemeinstellungen</td>
                    </tr>
                    <tr>
                        <td>admin.moderation</td>
                        <td>Moderation-Tools</td>
                    </tr>
                    <tr>
                        <td>admin.statistics</td>
                        <td>System-Statistiken</td>
                    </tr>
                    <tr>
                        <td rowspan="3"><strong>Teams</strong></td>
                        <td>teams.create</td>
                        <td>Teams erstellen</td>
                    </tr>
                    <tr>
                        <td>teams.manage</td>
                        <td>Eigene Teams verwalten</td>
                    </tr>
                    <tr>
                        <td>teams.apply</td>
                        <td>Bei Teams bewerben</td>
                    </tr>
                    <tr>
                        <td rowspan="3"><strong>Members</strong></td>
                        <td>members.kick</td>
                        <td>Mitglieder kicken</td>
                    </tr>
                    <tr>
                        <td>members.ban</td>
                        <td>Mitglieder bannen</td>
                    </tr>
                    <tr>
                        <td>members.warn</td>
                        <td>Verwarnungen aussprechen</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- APIs Section -->
        <div id="apis" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-code"></i> API Endpunkte</h2>
            
            <p>RESTful APIs für alle Framework-Funktionen mit Rate-Limiting und CORS-Support.</p>

            <h3>Authentication API (api/auth.php)</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Endpunkt</th>
                        <th>Methode</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>/api/auth.php?action=login</td>
                        <td>GET</td>
                        <td>Discord OAuth2 Login</td>
                    </tr>
                    <tr>
                        <td>/api/auth.php?logout=1</td>
                        <td>GET</td>
                        <td>Session beenden</td>
                    </tr>
                    <tr>
                        <td>/api/auth.php?action=userinfo</td>
                        <td>GET</td>
                        <td>Aktuelle Benutzer-Info</td>
                    </tr>
                </tbody>
            </table>

            <h3>Users API (api/users.php)</h3>
            <div class="code-block">
                <pre>// Benutzer kicken
fetch('/api/users.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'kick',
    user_id: 123,
    reason: 'Regelverstoß',
    platforms: ['discord', 'teamspeak']
  })
});</pre>
            </div>

            <h3>Teams API (api/teams.php)</h3>
            <div class="code-block">
                <pre>// Bei Team bewerben
fetch('/api/teams.php', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    action: 'apply',
    team_id: 1,
    message: 'Ich möchte dem Team beitreten',
    experience: '5 Jahre E-Sport Erfahrung'
  })
});</pre>
            </div>

            <h3>System API (api/system.php)</h3>
            <div class="code-block">
                <pre>// System-Status abrufen
fetch('/api/system.php?action=status')
  .then(response => response.json())
  .then(status => {
    console.log('Database:', status.database ? 'OK' : 'ERROR');
    console.log('Discord Bot:', status.discord_bot ? 'Online' : 'Offline');
  });</pre>
            </div>
        </div>

        <!-- Admin Section -->
        <div id="admin" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fas fa-tachometer-alt"></i> Admin Interface</h2>
            
            <p>Vollständiges Admin-Dashboard mit System-Übersicht und Verwaltungstools.</p>

            <div class="feature-grid">
                <div class="feature-card">
                    <h4><i class="fas fa-tachometer-alt"></i> Dashboard</h4>
                    <ul>
                        <li>System-Statistiken</li>
                        <li>Activity-Feed</li>
                        <li>Quick-Actions</li>
                        <li>Server-Status</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-users"></i> Benutzerverwaltung</h4>
                    <ul>
                        <li>Alle registrierten Benutzer</li>
                        <li>Rollen zuweisen</li>
                        <li>Status ändern</li>
                        <li>Paginierung & Filter</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-gavel"></i> Moderation</h4>
                    <ul>
                        <li>Cross-Platform Kick/Ban</li>
                        <li>Verwarnungen</li>
                        <li>Audit-Logs</li>
                        <li>Automatische Aktionen</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-cog"></i> Einstellungen</h4>
                    <ul>
                        <li>Discord-Konfiguration</li>
                        <li>TeamSpeak-Setup</li>
                        <li>Sicherheits-Optionen</li>
                        <li>Feature-Toggles</li>
                    </ul>
                </div>
            </div>

            <h3>Admin-Bereiche</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Datei</th>
                        <th>Berechtigung</th>
                        <th>Funktion</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>admin/dashboard.php</td>
                        <td>admin.settings</td>
                        <td>Hauptdashboard mit Übersicht</td>
                    </tr>
                    <tr>
                        <td>admin/users.php</td>
                        <td>admin.users</td>
                        <td>Komplette Benutzerverwaltung</td>
                    </tr>
                    <tr>
                        <td>admin/moderation.php</td>
                        <td>admin.moderation</td>
                        <td>Moderation-Tools und Logs</td>
                    </tr>
                    <tr>
                        <td>admin/settings.php</td>
                        <td>admin.settings</td>
                        <td>Systemkonfiguration</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Integrations Section -->
        <div id="integrations" class="section">
            <button class="back-btn" onclick="showNavigation()">
                <i class="fas fa-arrow-left"></i> Zurück zur Navigation
            </button>
            <h2><i class="fab fa-discord"></i> Integrationen</h2>
            
            <p>Vollständige Integration mit Discord und TeamSpeak für nahtlose Community-Verwaltung.</p>

            <div class="feature-grid">
                <div class="feature-card">
                    <h4><i class="fab fa-discord"></i> Discord Bot</h4>
                    <ul>
                        <li>OAuth2 Login Integration</li>
                        <li>Automatische Rollen-Sync</li>
                        <li>Moderation Commands</li>
                        <li>Event Benachrichtigungen</li>
                        <li>Status Updates</li>
                    </ul>
                </div>
                <div class="feature-card">
                    <h4><i class="fas fa-headset"></i> TeamSpeak</h4>
                    <ul>
                        <li>ServerQuery Integration</li>
                        <li>Automatische Server-Gruppen</li>
                        <li>Cross-Platform Moderation</li>
                        <li>Live-Status Monitoring</li>
                        <li>Channel-Management</li>
                    </ul>
                </div>
            </div>

            <h3>Discord Bot Commands</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>Command</th>
                        <th>Parameter</th>
                        <th>Berechtigung</th>
                        <th>Beschreibung</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>/kick</td>
                        <td>@user, reason</td>
                        <td>members.kick</td>
                        <td>Benutzer kicken</td>
                    </tr>
                    <tr>
                        <td>/ban</td>
                        <td>@user, reason, duration</td>
                        <td>members.ban</td>
                        <td>Benutzer bannen</td>
                    </tr>
                    <tr>
                        <td>/warn</td>
                        <td>@user, reason</td>
                        <td>members.warn</td>
                        <td>Verwarnung senden</td>
                    </tr>
                    <tr>
                        <td>/teaminfo</td>
                        <td>team_name</td>
                        <td>teams.view</td>
                        <td>Team-Informationen</td>
                    </tr>
                    <tr>
                        <td>/stats</td>
                        <td>@user (optional)</td>
                        <td>-</td>
                        <td>Benutzer-Statistiken</td>
                    </tr>
                </tbody>
            </table>

            <h3>TeamSpeak Konfiguration</h3>
            <div class="code-block">
                <pre>// TeamSpeak Configuration in config.php
'teamspeak' => [
    'host' => 'ts.v3ntom.de',
    'port' => '10011',          // ServerQuery Port
    'user' => 'serveradmin',
    'pass' => 'secure_password',
    'server_id' => '1',         // Virtual Server ID
    'default_channel' => '2'    // Lobby Channel ID
]</pre>
            </div>

            <h3>Rollen-Synchronisation</h3>
            <div class="code-block">
                <pre>// Framework-Rollen werden automatisch synchronisiert
$roleMapping = [
    'admin'     => 6,  // Server Admin Gruppe
    'moderator' => 10, // Moderator Gruppe  
    'member'    => 8,  // Member Gruppe
    'trial'     => 11, // Trial Gruppe
    'guest'     => 9   // Guest Gruppe
];

// Bei Rollen-Änderung wird TeamSpeak automatisch aktualisiert</pre>
            </div>

            <div class="alert alert-warning">
                <strong>Sicherheitshinweis:</strong> TeamSpeak ServerQuery-Zugangsdaten sollten nur die notwendigen Berechtigungen haben, niemals volle ServerAdmin-Rechte.
            </div>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            // Hide navigation
            document.getElementById('navigation').style.display = 'none';
            
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
            
            // Show selected section
            document.getElementById(sectionId).classList.add('active');
        }

        function showNavigation() {
            // Show navigation
            document.getElementById('navigation').style.display = 'grid';
            
            // Hide all sections
            document.querySelectorAll('.section').forEach(section => {
                section.classList.remove('active');
            });
        }

        // Initialize - show navigation by default
        document.addEventListener('DOMContentLoaded', function() {
            showNavigation();
        });
    </script>
</body>
</html>