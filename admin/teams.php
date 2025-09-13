<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

Security::checkAuth('admin.teams');
$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);

// Handle actions
$message = '';
$error = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_team':
            try {
                $teamData = [
                    'name' => Security::sanitizeInput($_POST['name']),
                    'slug' => strtolower(str_replace([' ', '.', '/'], '-', Security::sanitizeInput($_POST['name']))),
                    'description' => Security::sanitizeInput($_POST['description']),
                    'game' => Security::sanitizeInput($_POST['game']),
                    'leader_id' => !empty($_POST['leader_id']) ? (int)$_POST['leader_id'] : null,
                    'max_members' => (int)$_POST['max_members'],
                    'recruitment_open' => isset($_POST['recruitment_open']) ? 1 : 0,
                    'color' => $_POST['color'] ?? '#5865f2',
                    'requirements' => json_encode([
                        'min_age' => (int)($_POST['min_age'] ?? 0),
                        'experience' => $_POST['experience'] ?? '',
                        'availability' => $_POST['availability'] ?? ''
                    ])
                ];
                
                $teamId = $db->insert('teams', $teamData);
                
                // Add leader as team member
                if ($teamData['leader_id']) {
                    $db->insert('team_members', [
                        'team_id' => $teamId,
                        'user_id' => $teamData['leader_id'],
                        'role' => 'leader'
                    ]);
                }
                
                $message = 'Team "' . htmlspecialchars($teamData['name']) . '" erfolgreich erstellt';
            } catch (Exception $e) {
                $error = 'Fehler beim Erstellen des Teams: ' . $e->getMessage();
            }
            break;
            
        case 'update_recruitment':
            $teamId = (int)$_POST['team_id'];
            $recruitmentOpen = isset($_POST['recruitment_open']) ? 1 : 0;
            
            if ($permissions->canManageTeam($_SESSION['user_id'], $teamId)) {
                $db->update('teams', ['recruitment_open' => $recruitmentOpen], 'id = ?', [$teamId]);
                $message = 'Recruitment-Status aktualisiert';
            } else {
                $error = 'Keine Berechtigung dieses Team zu verwalten';
            }
            break;
            
        case 'delete_team':
            $teamId = (int)$_POST['team_id'];
            
            if ($permissions->canManageTeam($_SESSION['user_id'], $teamId)) {
                // Delete team (cascade will handle members and applications)
                $db->delete('teams', 'id = ?', [$teamId]);
                $message = 'Team erfolgreich gelöscht';
            } else {
                $error = 'Keine Berechtigung dieses Team zu löschen';
            }
            break;
            
        case 'approve_application':
            $applicationId = (int)$_POST['application_id'];
            $application = $db->fetch("SELECT * FROM team_applications WHERE id = ?", [$applicationId]);
            
            if ($application && $permissions->canManageTeam($_SESSION['user_id'], $application['team_id'])) {
                // Update application status
                $db->update('team_applications', [
                    'status' => 'approved',
                    'reviewed_by' => $_SESSION['user_id'],
                    'reviewed_at' => date('Y-m-d H:i:s')
                ], 'id = ?', [$applicationId]);
                
                // Add user to team
                $db->insert('team_members', [
                    'team_id' => $application['team_id'],
                    'user_id' => $application['user_id'],
                    'role' => 'member'
                ]);
                
                $message = 'Bewerbung genehmigt und Mitglied hinzugefügt';
            } else {
                $error = 'Bewerbung nicht gefunden oder keine Berechtigung';
            }
            break;
            
        case 'reject_application':
            $applicationId = (int)$_POST['application_id'];
            $application = $db->fetch("SELECT * FROM team_applications WHERE id = ?", [$applicationId]);
            
            if ($application && $permissions->canManageTeam($_SESSION['user_id'], $application['team_id'])) {
                $db->update('team_applications', [
                    'status' => 'rejected',
                    'reviewed_by' => $_SESSION['user_id'],
                    'reviewed_at' => date('Y-m-d H:i:s'),
                    'review_notes' => Security::sanitizeInput($_POST['review_notes'] ?? '')
                ], 'id = ?', [$applicationId]);
                
                $message = 'Bewerbung abgelehnt';
            } else {
                $error = 'Bewerbung nicht gefunden oder keine Berechtigung';
            }
            break;
    }
}

// Get teams with statistics
$teams = $db->fetchAll("
    SELECT t.*, 
           u.username as leader_name,
           (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id) as member_count,
           (SELECT COUNT(*) FROM team_applications ta WHERE ta.team_id = t.id AND ta.status = 'pending') as pending_applications
    FROM teams t 
    LEFT JOIN users u ON t.leader_id = u.id 
    ORDER BY t.created_at DESC
");

// Get pending applications
$applications = $db->fetchAll("
    SELECT ta.*, t.name as team_name, u.username as applicant_name, u.discord_id
    FROM team_applications ta
    JOIN teams t ON ta.team_id = t.id
    JOIN users u ON ta.user_id = u.id
    WHERE ta.status = 'pending'
    ORDER BY ta.created_at DESC
");

// Get all users for team leader selection
$users = $db->fetchAll("SELECT id, username FROM users WHERE status = 'active' ORDER BY username");
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Team-Management - V3NTOM Admin</title>
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
        
        .content-tabs {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .tab-btn {
            padding: 0.75rem 1.5rem;
            background: #40444b;
            color: #dcddde;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .tab-btn.active {
            background: #5865f2;
            color: white;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .teams-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .team-card {
            background: #2f3136;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #40444b;
            position: relative;
            transition: all 0.3s;
        }
        
        .team-card:hover {
            border-color: #5865f2;
            transform: translateY(-2px);
        }
        
        .team-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .team-color {
            width: 4px;
            height: 60px;
            border-radius: 2px;
            margin-right: 1rem;
        }
        
        .team-info h3 {
            margin-bottom: 0.25rem;
            color: #ffffff;
        }
        
        .team-game {
            color: #72767d;
            font-size: 0.9rem;
        }
        
        .team-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .stat {
            text-align: center;
            padding: 0.5rem;
            background: #40444b;
            border-radius: 4px;
        }
        
        .stat-number {
            font-size: 1.2rem;
            font-weight: bold;
            color: #57f287;
        }
        
        .stat-label {
            font-size: 0.8rem;
            color: #72767d;
        }
        
        .recruitment-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .recruitment-open {
            background: #57f287;
            color: #000;
        }
        
        .recruitment-closed {
            background: #ed4245;
            color: #fff;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #dcddde;
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
            margin: 0.25rem;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary { background: #5865f2; color: white; }
        .btn-success { background: #57f287; color: black; }
        .btn-warning { background: #ffa500; color: black; }
        .btn-danger { background: #ed4245; color: white; }
        .btn-secondary { background: #72767d; color: white; }
        
        .btn:hover { opacity: 0.8; }
        
        .btn-sm {
            padding: 0.5rem 1rem;
            font-size: 0.9rem;
        }
        
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
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
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
        
        .applications-list {
            max-height: 500px;
            overflow-y: auto;
        }
        
        .application-card {
            background: #2f3136;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid #40444b;
        }
        
        .application-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .application-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        @media (max-width: 768px) {
            .teams-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .form-grid {
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
        <a href="teams.php" class="active">Teams</a>
        <a href="moderation.php">Moderation</a>
        <a href="settings.php">Einstellungen</a>
    </nav>
    
    <div class="container">
        <div class="card">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h2><i class="fas fa-users-cog"></i> Team-Management</h2>
                    <p>Verwalte alle E-Sport Teams, Bewerbungen und Rekrutierung.</p>
                </div>
                <button class="btn btn-primary" onclick="openModal('createModal')">
                    <i class="fas fa-plus"></i> Neues Team erstellen
                </button>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <!-- Content Tabs -->
        <div class="content-tabs">
            <button class="tab-btn active" onclick="showTab('teams')">
                <i class="fas fa-users-cog"></i> Teams (<?= count($teams) ?>)
            </button>
            <button class="tab-btn" onclick="showTab('applications')">
                <i class="fas fa-inbox"></i> Bewerbungen (<?= count($applications) ?>)
            </button>
        </div>
        
        <!-- Teams Tab -->
        <div id="teams" class="tab-content active">
            <?php if (empty($teams)): ?>
                <div class="card" style="text-align: center; padding: 3rem;">
                    <i class="fas fa-users-cog" style="font-size: 3rem; color: #72767d; margin-bottom: 1rem;"></i>
                    <h3>Keine Teams vorhanden</h3>
                    <p style="color: #72767d; margin-bottom: 2rem;">Erstelle das erste E-Sport Team für deine Community.</p>
                    <button class="btn btn-primary" onclick="openModal('createModal')">
                        <i class="fas fa-plus"></i> Erstes Team erstellen
                    </button>
                </div>
            <?php else: ?>
                <div class="teams-grid">
                    <?php foreach ($teams as $team): ?>
                        <div class="team-card">
                            <div class="recruitment-badge <?= $team['recruitment_open'] ? 'recruitment-open' : 'recruitment-closed' ?>">
                                <?= $team['recruitment_open'] ? 'Rekrutiert' : 'Geschlossen' ?>
                            </div>
                            
                            <div class="team-header">
                                <div class="team-color" style="background-color: <?= $team['color'] ?>;"></div>
                                <div class="team-info">
                                    <h3><?= htmlspecialchars($team['name']) ?></h3>
                                    <div class="team-game"><?= htmlspecialchars($team['game'] ?? 'Kein Spiel') ?></div>
                                </div>
                            </div>
                            
                            <div class="team-stats">
                                <div class="stat">
                                    <div class="stat-number"><?= $team['member_count'] ?></div>
                                    <div class="stat-label">Mitglieder</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number"><?= $team['max_members'] ?></div>
                                    <div class="stat-label">Maximum</div>
                                </div>
                                <div class="stat">
                                    <div class="stat-number"><?= $team['pending_applications'] ?></div>
                                    <div class="stat-label">Bewerbungen</div>
                                </div>
                            </div>
                            
                            <p style="color: #72767d; margin-bottom: 1rem; font-size: 0.9rem;">
                                <?= htmlspecialchars(substr($team['description'] ?? '', 0, 100)) ?>
                                <?= strlen($team['description'] ?? '') > 100 ? '...' : '' ?>
                            </p>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <small style="color: #72767d;">
                                    Leader: <?= htmlspecialchars($team['leader_name'] ?? 'Keiner') ?>
                                </small>
                                
                                <div style="display: flex; align-items: center; gap: 0.5rem;">
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_recruitment">
                                        <input type="hidden" name="team_id" value="<?= $team['id'] ?>">
                                        <input type="checkbox" name="recruitment_open" <?= $team['recruitment_open'] ? 'checked' : '' ?> 
                                               onchange="this.form.submit()" class="checkbox">
                                        <label style="font-size: 0.9rem;">Rekrutierung</label>
                                    </form>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 0.5rem;">
                                <?php if ($permissions->canManageTeam($_SESSION['user_id'], $team['id'])): ?>
                                    <button class="btn btn-danger btn-sm" 
                                            onclick="deleteTeam(<?= $team['id'] ?>, '<?= htmlspecialchars($team['name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Applications Tab -->
        <div id="applications" class="tab-content">
            <div class="card">
                <h3><i class="fas fa-inbox"></i> Offene Bewerbungen</h3>
                
                <?php if (empty($applications)): ?>
                    <div style="text-align: center; padding: 2rem;">
                        <i class="fas fa-inbox" style="font-size: 2rem; color: #72767d; margin-bottom: 1rem; display: block;"></i>
                        <p style="color: #72767d;">Keine offenen Bewerbungen vorhanden</p>
                    </div>
                <?php else: ?>
                    <div class="applications-list">
                        <?php foreach ($applications as $app): ?>
                            <div class="application-card">
                                <div class="application-header">
                                    <div>
                                        <strong><?= htmlspecialchars($app['applicant_name']) ?></strong>
                                        <span style="color: #72767d;">bewirbt sich für</span>
                                        <strong style="color: #5865f2;"><?= htmlspecialchars($app['team_name']) ?></strong>
                                    </div>
                                    <small style="color: #72767d;">
                                        <?= date('d.m.Y H:i', strtotime($app['created_at'])) ?>
                                    </small>
                                </div>
                                
                                <?php if ($app['message']): ?>
                                    <div style="margin: 1rem 0;">
                                        <strong>Nachricht:</strong>
                                        <p style="color: #dcddde; background: #40444b; padding: 0.5rem; border-radius: 4px; margin-top: 0.5rem;">
                                            <?= nl2br(htmlspecialchars($app['message'])) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($app['experience']): ?>
                                    <div style="margin: 1rem 0;">
                                        <strong>Erfahrung:</strong>
                                        <p style="color: #dcddde; margin-top: 0.5rem;">
                                            <?= nl2br(htmlspecialchars($app['experience'])) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($permissions->canManageTeam($_SESSION['user_id'], $app['team_id'])): ?>
                                    <div class="application-actions">
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="approve_application">
                                            <input type="hidden" name="application_id" value="<?= $app['id'] ?>">
                                            <button type="submit" class="btn btn-success btn-sm">
                                                <i class="fas fa-check"></i> Genehmigen
                                            </button>
                                        </form>
                                        
                                        <button class="btn btn-danger btn-sm" 
                                                onclick="rejectApplication(<?= $app['id'] ?>)">
                                            <i class="fas fa-times"></i> Ablehnen
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <p style="color: #72767d; font-style: italic;">Keine Berechtigung diese Bewerbung zu verwalten</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Create Team Modal -->
    <div id="createModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Neues Team erstellen</h3>
                <button class="close" onclick="closeModal('createModal')">&times;</button>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="create_team">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Team Name *</label>
                        <input type="text" name="name" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Spiel</label>
                        <input type="text" name="game" class="form-input" placeholder="z.B. CS2, Valorant, LoL">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Team Leader</label>
                        <select name="leader_id" class="form-select">
                            <option value="">Keiner</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Max. Mitglieder</label>
                        <input type="number" name="max_members" class="form-input" value="10" min="1" max="50">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Team Farbe</label>
                        <input type="color" name="color" class="form-input" value="#5865f2">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Mindestalter</label>
                        <input type="number" name="min_age" class="form-input" value="0" min="0" max="25">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Beschreibung</label>
                    <textarea name="description" class="form-textarea" 
                              placeholder="Team Beschreibung, Ziele, Spielstil..."></textarea>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Erfahrungsanforderungen</label>
                    <input type="text" name="experience" class="form-input" 
                           placeholder="z.B. Mindestens Gold-Rang, 2 Jahre Erfahrung">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Verfügbarkeitsanforderungen</label>
                    <input type="text" name="availability" class="form-input" 
                           placeholder="z.B. 3x pro Woche abends, Wochenenden">
                </div>
                
                <div class="checkbox-group">
                    <input type="checkbox" name="recruitment_open" checked class="checkbox">
                    <label>Rekrutierung sofort öffnen</label>
                </div>
                
                <div style="text-align: right; margin-top: 2rem; border-top: 1px solid #40444b; padding-top: 1rem;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Team erstellen</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function showTab(tabName) {
            // Hide all tab contents
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Remove active class from all tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab content
            document.getElementById(tabName).classList.add('active');
            
            // Add active class to clicked tab button
            event.target.classList.add('active');
        }
        
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }
        
        function deleteTeam(teamId, teamName) {
            if (confirm(`Möchtest du das Team "${teamName}" wirklich löschen?\n\nAlle Mitglieder und Bewerbungen werden ebenfalls entfernt!`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_team">
                    <input type="hidden" name="team_id" value="${teamId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function rejectApplication(applicationId) {
            const reason = prompt('Grund für die Ablehnung (optional):');
            if (reason !== null) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="reject_application">
                    <input type="hidden" name="application_id" value="${applicationId}">
                    <input type="hidden" name="review_notes" value="${reason}">
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
        
        // Auto-generate team slug from name
        document.querySelector('input[name="name"]').addEventListener('input', function() {
            const name = this.value;
            const slug = name.toLowerCase().replace(/[^a-z0-9]/g, '-').replace(/-+/g, '-');
            console.log('Generated slug:', slug);
        });
    </script>
</body>
</html>