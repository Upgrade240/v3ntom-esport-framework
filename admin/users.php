<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

Security::checkAuth('admin.users');
$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);

// Handle actions
$message = '';
$error = '';

if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_role':
            $userId = (int)$_POST['user_id'];
            $roleId = (int)$_POST['role_id'];
            
            if ($permissions->canManageUser($_SESSION['user_id'], $userId)) {
                $db->update('users', ['role_id' => $roleId], 'id = ?', [$userId]);
                $message = 'Rolle erfolgreich aktualisiert';
            } else {
                $error = 'Keine Berechtigung diesen Benutzer zu verwalten';
            }
            break;
            
        case 'update_status':
            $userId = (int)$_POST['user_id'];
            $status = $_POST['status'];
            
            if ($permissions->canManageUser($_SESSION['user_id'], $userId)) {
                $db->update('users', ['status' => $status], 'id = ?', [$userId]);
                $message = 'Status erfolgreich aktualisiert';
            } else {
                $error = 'Keine Berechtigung diesen Benutzer zu verwalten';
            }
            break;
    }
}

// Get users
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$result = $db->query("SELECT u.*, r.display_name as role_name, r.color as role_color, r.hierarchy_level 
                     FROM users u 
                     LEFT JOIN roles r ON u.role_id = r.id 
                     ORDER BY u.created_at DESC 
                     LIMIT ? OFFSET ?", [$limit, $offset]);
$users = $db->fetchAll($result);

$totalResult = $db->query("SELECT COUNT(*) as count FROM users");
$totalUsers = $db->fetchOne($totalResult)['count'];
$totalPages = ceil($totalUsers / $limit);

// Get roles for dropdown
$rolesResult = $db->query("SELECT * FROM roles ORDER BY hierarchy_level DESC");
$roles = $db->fetchAll($rolesResult);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung - V3NTOM Admin</title>
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

        .card {
            background: var(--card-bg);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
        }

        .card h2 {
            color: var(--text);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card p {
            color: var(--text-muted);
            margin-bottom: 1rem;
        }

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

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--darker);
            border-radius: 6px;
            padding: 1rem;
            text-align: center;
        }

        .stat-card h3 {
            font-size: 1.8rem;
            color: var(--primary);
            margin-bottom: 0.25rem;
        }

        .stat-card p {
            color: var(--text-muted);
            font-size: 0.9rem;
            margin: 0;
        }

        .table-container {
            overflow-x: auto;
            border-radius: 8px;
            border: 1px solid var(--border);
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
            font-weight: 600;
            color: var(--text);
        }

        tr:hover {
            background: rgba(88, 101, 242, 0.05);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info strong {
            color: var(--text);
        }

        .user-info small {
            color: var(--text-muted);
        }

        .role-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
            display: inline-block;
        }

        .status-active { 
            background: rgba(87, 242, 135, 0.2); 
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .status-trial { 
            background: rgba(255, 165, 0, 0.2); 
            color: var(--warning);
            border: 1px solid var(--warning);
        }
        
        .status-inactive { 
            background: rgba(114, 118, 125, 0.2); 
            color: var(--text-muted);
            border: 1px solid var(--text-muted);
        }
        
        .status-banned { 
            background: rgba(237, 66, 69, 0.2); 
            color: var(--danger);
            border: 1px solid var(--danger);
        }

        .action-controls {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        select {
            background: var(--darker);
            color: var(--text);
            border: 1px solid var(--border);
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 0.9rem;
            cursor: pointer;
        }

        select:focus {
            border-color: var(--primary);
            outline: none;
        }

        .no-permission {
            color: var(--text-muted);
            font-style: italic;
        }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a {
            padding: 0.5rem 1rem;
            background: var(--darker);
            color: var(--text);
            text-decoration: none;
            border-radius: 4px;
            border: 1px solid var(--border);
            transition: all 0.3s;
        }

        .pagination a:hover, .pagination a.active {
            background: var(--primary);
            border-color: var(--primary);
        }

        .search-filters {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-box {
            flex: 1;
            min-width: 200px;
        }

        .search-box input {
            width: 100%;
            padding: 0.75rem;
            background: var(--darker);
            border: 1px solid var(--border);
            border-radius: 6px;
            color: var(--text);
            font-size: 0.9rem;
        }

        .search-box input:focus {
            border-color: var(--primary);
            outline: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .search-filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .action-controls {
                flex-direction: column;
                gap: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><i class="fas fa-users"></i> V3NTOM Admin - Benutzerverwaltung</h1>
        <div class="user-info">
            <span>Willkommen, Admin</span>
            <a href="../api/auth.php?logout" style="color: var(--danger);">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>

    <nav class="nav">
        <a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="users.php" class="active"><i class="fas fa-users"></i> Benutzer</a>
        <a href="teams.php"><i class="fas fa-users-cog"></i> Teams</a>
        <a href="moderation.php"><i class="fas fa-shield-alt"></i> Moderation</a>
        <a href="settings.php"><i class="fas fa-cog"></i> Einstellungen</a>
    </nav>

    <div class="container">
        <div class="card">
            <h2><i class="fas fa-users-cog"></i> Benutzerverwaltung</h2>
            <p>Verwalte alle registrierten Benutzer, deren Rollen und Status. Hier können Administratoren Benutzerrechte anpassen und den Status von Mitgliedern ändern.</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?= number_format($totalUsers) ?></h3>
                <p>Gesamte Benutzer</p>
            </div>
            <div class="stat-card">
                <h3><?= count($roles) ?></h3>
                <p>Verfügbare Rollen</p>
            </div>
            <div class="stat-card">
                <h3><?= $totalPages ?></h3>
                <p>Seiten</p>
            </div>
            <div class="stat-card">
                <h3><?= $page ?></h3>
                <p>Aktuelle Seite</p>
            </div>
        </div>

        <div class="card">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th><i class="fas fa-user"></i> Benutzer</th>
                            <th><i class="fas fa-shield-alt"></i> Rolle</th>
                            <th><i class="fas fa-circle"></i> Status</th>
                            <th><i class="fas fa-calendar"></i> Registriert</th>
                            <th><i class="fas fa-cogs"></i> Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-info">
                                        <?php if ($user['avatar']): ?>
                                            <img src="https://cdn.discordapp.com/avatars/<?= $user['discord_id'] ?>/<?= $user['avatar'] ?>.png" 
                                                 class="user-avatar" alt="Avatar">
                                        <?php else: ?>
                                            <div class="user-avatar" style="background: var(--primary); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                                <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                            <small>ID: <?= $user['discord_id'] ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="role-badge" style="background-color: <?= $user['role_color'] ?? 'var(--primary)' ?>; color: <?= $user['role_color'] ? '#000' : '#fff' ?>;">
                                        <?= htmlspecialchars($user['role_name'] ?? 'Keine Rolle') ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?= $user['status'] ?>">
                                        <i class="fas fa-circle" style="font-size: 0.6rem;"></i>
                                        <?= ucfirst($user['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span><?= date('d.m.Y', strtotime($user['created_at'])) ?></span><br>
                                    <small style="color: var(--text-muted);"><?= date('H:i', strtotime($user['created_at'])) ?> Uhr</small>
                                </td>
                                <td>
                                    <?php if ($permissions->canManageUser($_SESSION['user_id'], $user['id'])): ?>
                                        <div class="action-controls">
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="role_id" onchange="this.form.submit()" title="Rolle ändern">
                                                    <option value="">Rolle wählen...</option>
                                                    <?php foreach ($roles as $role): ?>
                                                        <option value="<?= $role['id'] ?>" <?= $role['id'] == $user['role_id'] ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars($role['display_name']) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                            
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <select name="status" onchange="this.form.submit()" title="Status ändern">
                                                    <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Aktiv</option>
                                                    <option value="trial" <?= $user['status'] == 'trial' ? 'selected' : '' ?>>Trial</option>
                                                    <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inaktiv</option>
                                                    <option value="banned" <?= $user['status'] == 'banned' ? 'selected' : '' ?>>Gebannt</option>
                                                </select>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="no-permission">
                                            <i class="fas fa-lock"></i> Keine Berechtigung
                                        </span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1"><i class="fas fa-angle-double-left"></i></a>
                        <a href="?page=<?= $page - 1 ?>"><i class="fas fa-angle-left"></i></a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++): 
                    ?>
                        <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>"><i class="fas fa-angle-right"></i></a>
                        <a href="?page=<?= $totalPages ?>"><i class="fas fa-angle-double-right"></i></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>