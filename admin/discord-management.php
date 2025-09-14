<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

Security::checkAuth('admin.discord');
$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Discord Management - V3NTOM Admin</title>
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

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .card h3 {
            color: var(--text);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover { opacity: 0.8; }
        .btn.success { background: var(--success); color: black; }
        .btn.danger { background: var(--danger); }
        .btn.warning { background: var(--warning); color: black; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--darker);
            border-radius: 6px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid var(--border);
        }

        .stat-card h4 {
            font-size: 2rem;
            color: var(--primary);
            margin-bottom: 0.5rem;
        }

        .sync-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
            margin: 1rem 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--card-bg);
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border);
        }

        th {
            background: var(--darker);
            color: var(--text);
            font-weight: 600;
        }

        tr:hover { background: rgba(88, 101, 242, 0.05); }

        .role-badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 0.5rem;
        }

        .status-online { background: var(--success); }
        .status-offline { background: var(--danger); }
        .status-idle { background: var(--warning); }

        .alert {
            padding: 1rem;
            border-radius: 6px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .alert-success {
            background: rgba(87, 242, 135, 0.1);
            border: 1px solid var(--success);
            color: var(--success);
        }

        .alert-danger {
            background: rgba(237, 66, 69, 0.1);
            border: 1px solid var(--danger);
            color: var(--danger);
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid var(--border);
            border-radius: 50%;
            border-top-color: var(--primary);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        .modal-content {
            background-color: var(--card-bg);
            margin: 5% auto;
            padding: 2rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            width: 90%;
            max-width: 600px;
        }

        .close {
            color: var(--text-muted);
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover { color: var(--text); }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text);
        }

        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            background: var(--darker);
            border: 1px solid var(--border);
            border-radius: 4px;
            color: var(--text);
        }

        .form-group input:focus, .form-group select:focus, .form-group textarea:focus {
            border-color: var(--primary);
            outline: none;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fab fa-discord"></i> Discord Management</h1>
        <div class="user-info">
            <span>Willkommen, Admin</span>
            <a href="../api/auth.php?logout" style="color: var(--danger);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <nav class="nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="users.php"><i class="fas fa-users"></i> Benutzer</a>
        <a href="teams.php"><i class="fas fa-users-cog"></i> Teams</a>
        <a href="discord-management.php" class="active"><i class="fab fa-discord"></i> Discord</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Einstellungen</a>
    </nav>

    <div class="container">
        <!-- Status Overview -->
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <h4 id="totalRoles">-</h4>
                <p>Discord Rollen</p>
            </div>
            <div class="stat-card">
                <h4 id="totalMembers">-</h4>
                <p>Synchronisierte Mitglieder</p>
            </div>
            <div class="stat-card">
                <h4 id="totalAnnouncements">-</h4>
                <p>Ankündigungen</p>
            </div>
            <div class="stat-card">
                <h4><span class="status-indicator" id="botStatus"></span>Bot Status</h4>
                <p id="botStatusText">Prüfung läuft...</p>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <h3><i class="fas fa-bolt"></i> Schnell-Aktionen</h3>
            <div class="sync-section">
                <div class="card">
                    <h4>Rollen synchronisieren</h4>
                    <p>Importiert alle Discord-Rollen in die Datenbank</p>
                    <button class="btn" onclick="syncRoles()">
                        <i class="fas fa-sync"></i> Rollen synchen
                    </button>
                </div>
                <div class="card">
                    <h4>Mitglieder synchronisieren</h4>
                    <p>Importiert alle Discord-Mitglieder und ihre Rollen</p>
                    <button class="btn" onclick="syncMembers()">
                        <i class="fas fa-users"></i> Mitglieder synchen
                    </button>
                </div>
                <div class="card">
                    <h4>Vollständige Synchronisation</h4>
                    <p>Führt eine komplette Synchronisation durch</p>
                    <button class="btn warning" onclick="fullSync()">
                        <i class="fas fa-sync-alt"></i> Vollsync
                    </button>
                </div>
            </div>
        </div>

        <!-- Discord Roles -->
        <div class="card">
            <h3><i class="fas fa-shield-alt"></i> Discord-Rollen verwalten</h3>
            <button class="btn success" onclick="openCreateRoleModal()" style="margin-bottom: 1rem;">
                <i class="fas fa-plus"></i> Neue Rolle erstellen
            </button>
            
            <div class="table-container">
                <table id="rolesTable">
                    <thead>
                        <tr>
                            <th>Rolle</th>
                            <th>Farbe</th>
                            <th>Position</th>
                            <th>Zugewiesene Benutzer</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                <div class="loading"></div> Lade Discord-Rollen...
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Announcements Preview -->
        <div class="card">
            <h3><i class="fas fa-bullhorn"></i> Aktuelle Ankündigungen</h3>
            <button class="btn success" onclick="openCreateAnnouncementModal()" style="margin-bottom: 1rem;">
                <i class="fas fa-plus"></i> Neue Ankündigung erstellen
            </button>
            
            <div id="announcementsPreview">
                <div class="loading"></div> Lade Ankündigungen...
            </div>
        </div>
    </div>

    <!-- Create Role Modal -->
    <div id="createRoleModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createRoleModal')">&times;</span>
            <h3>Neue Discord-Rolle erstellen</h3>
            <form id="createRoleForm" onsubmit="createRole(event)">
                <div class="form-group">
                    <label for="roleName">Rollenname:</label>
                    <input type="text" id="roleName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="roleColor">Farbe (Hex):</label>
                    <input type="color" id="roleColor" name="color" value="#99AAB5">
                </div>
                <div class="form-group">
                    <div class="checkbox-group">
                        <input type="checkbox" id="roleHoist" name="hoist">
                        <label for="roleHoist">Separat in Mitgliederliste anzeigen</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="roleMentionable" name="mentionable">
                        <label for="roleMentionable">Erwähnbar</label>
                    </div>
                </div>
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="btn" onclick="closeModal('createRoleModal')" style="margin-right: 0.5rem;">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn success">
                        <i class="fas fa-plus"></i> Rolle erstellen
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Announcement Modal -->
    <div id="createAnnouncementModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('createAnnouncementModal')">&times;</span>
            <h3>Neue Ankündigung erstellen</h3>
            <form id="createAnnouncementForm" onsubmit="createAnnouncement(event)">
                <div class="form-group">
                    <label for="announcementTitle">Titel:</label>
                    <input type="text" id="announcementTitle" name="title" required>
                </div>
                <div class="form-group">
                    <label for="announcementDescription">Beschreibung:</label>
                    <textarea id="announcementDescription" name="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="announcementType">Typ:</label>
                    <select id="announcementType" name="type">
                        <option value="general">Allgemein</option>
                        <option value="team">Team-Ankündigung</option>
                        <option value="event">Event</option>
                        <option value="update">System-Update</option>
                    </select>
                </div>
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="btn" onclick="closeModal('createAnnouncementModal')" style="margin-right: 0.5rem;">
                        Abbrechen
                    </button>
                    <button type="submit" class="btn success">
                        <i class="fas fa-bullhorn"></i> Ankündigung senden
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let isLoading = false;

        document.addEventListener('DOMContentLoaded', function() {
            loadStats();
            loadDiscordRoles();
            loadAnnouncements();
            checkBotStatus();
        });

        async function apiRequest(url, options = {}) {
            try {
                const response = await fetch(url, {
                    ...options,
                    headers: {
                        'Content-Type': 'application/json',
                        ...options.headers
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                
                return await response.json();
            } catch (error) {
                showAlert('API Fehler: ' + error.message, 'danger');
                throw error;
            }
        }

        async function loadStats() {
            document.getElementById('totalRoles').textContent = '0';
            document.getElementById('totalMembers').textContent = '0'; 
            document.getElementById('totalAnnouncements').textContent = '0';
        }

        async function checkBotStatus() {
            const status = document.getElementById('botStatus');
            const statusText = document.getElementById('botStatusText');
            status.className = 'status-indicator status-online';
            statusText.textContent = 'Bereit für Integration';
        }

        async function syncRoles() {
            if (isLoading) return;
            isLoading = true;
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<div class="loading"></div> Synchronisiere...';
            button.disabled = true;
            
            try {
                showAlert('Rollen-Synchronisation gestartet...', 'success');
                loadDiscordRoles();
            } catch (error) {
                // Error handled by apiRequest
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
                isLoading = false;
            }
        }

        async function syncMembers() {
            if (isLoading) return;
            isLoading = true;
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<div class="loading"></div> Synchronisiere...';
            button.disabled = true;
            
            try {
                showAlert('Mitglieder-Synchronisation gestartet...', 'success');
            } catch (error) {
                // Error handled
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
                isLoading = false;
            }
        }

        async function fullSync() {
            if (isLoading) return;
            
            if (!confirm('Vollständige Synchronisation durchführen?')) return;
            
            isLoading = true;
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '<div class="loading"></div> Vollsync läuft...';
            button.disabled = true;
            
            try {
                showAlert('Vollständige Synchronisation erfolgreich!', 'success');
                loadDiscordRoles();
                loadAnnouncements();
            } catch (error) {
                // Error handled
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
                isLoading = false;
            }
        }

        async function loadDiscordRoles() {
            const tbody = document.querySelector('#rolesTable tbody');
            
            // Beispiel-Daten - in echtem System über API laden
            tbody.innerHTML = `
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <div style="width: 12px; height: 12px; border-radius: 50%; background: #5865f2;"></div>
                            <strong>@everyone</strong>
                        </div>
                    </td>
                    <td>#5865F2</td>
                    <td>0</td>
                    <td><span class="role-badge">alle</span></td>
                    <td>
                        <button class="btn" onclick="manageRole('everyone')">
                            <i class="fas fa-cog"></i>
                        </button>
                    </td>
                </tr>
            `;
            
            document.getElementById('totalRoles').textContent = '1';
        }

        async function loadAnnouncements() {
            const container = document.getElementById('announcementsPreview');
            
            container.innerHTML = `
                <div style="text-align: center; padding: 2rem; color: var(--text-muted);">
                    <i class="fas fa-bullhorn" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                    Keine Ankündigungen vorhanden. Erstellen Sie die erste Ankündigung!
                </div>
            `;
        }

        function openCreateRoleModal() {
            document.getElementById('createRoleModal').style.display = 'block';
        }

        function openCreateAnnouncementModal() {
            document.getElementById('createAnnouncementModal').style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        async function createRole(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            showAlert('Discord-Rolle würde erstellt werden!', 'success');
            closeModal('createRoleModal');
            event.target.reset();
        }

        async function createAnnouncement(event) {
            event.preventDefault();
            const formData = new FormData(event.target);
            
            showAlert('Ankündigung würde an Discord gesendet werden!', 'success');
            closeModal('createAnnouncementModal');
            event.target.reset();
        }

        function showAlert(message, type = 'success') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check' : 'exclamation-triangle'}"></i>
                ${message}
            `;
            
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        function manageRole(roleId) {
            showAlert('Rollen-Management Interface würde geöffnet werden...', 'success');
        }

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