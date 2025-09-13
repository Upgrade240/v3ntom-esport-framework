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

$users = $db->fetchAll("SELECT u.*, r.display_name as role_name, r.color as role_color, r.hierarchy_level 
                       FROM users u 
                       LEFT JOIN roles r ON u.role_id = r.id 
                       ORDER BY u.created_at DESC 
                       LIMIT ? OFFSET ?", [$limit, $offset]);

$totalUsers = $db->fetch("SELECT COUNT(*) as count FROM users")['count'];
$totalPages = ceil($totalUsers / $limit);

$roles = $db->getAllRoles();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Benutzerverwaltung - V3NTOM Admin</title>
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
            margin-bottom: 1rem;
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
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: #2f3136;
            border-radius: 8px;
            overflow: hidden;
        }
        
        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #40444b;
        }
        
        th {
            background: #202225;
            font-weight: 600;
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
        
        .role-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active { background: #57f287; color: #000; }
        .status-trial { background: #ffa500; color: #000; }
        .status-inactive { background: #72767d; color: #fff; }
        .status-banned { background: #ed4245; color: #fff; }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            margin: 0.25rem;
            transition: all 0.3s;
        }
        
        .btn-primary { background: #5865f2; color: white; }
        .btn-success { background: #57f287; color: black; }
        .btn-warning { background: #ffa500; color: black; }
        .btn-danger { background: #ed4245; color: white; }
        
        .btn:hover { opacity: 0.8; }
        
        select {
            background: #40444b;
            color: #dcddde;
            border: 1px solid #72767d;
            padding: 0.5rem;
            border-radius: 4px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .pagination a {
            padding: 0.5rem 1rem;
            background: #40444b;
            color: #dcddde;
            text-decoration: none;
            border-radius: 4px;
        }
        
        .pagination a:hover, .pagination a.active {
            background: #5865f2;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>V3NTOM Admin Panel</h1>
        <a href="../api/auth.php?logout" style="color: #ed4245;">Logout</a>
    </div>
    
    <nav class="nav">
        <a href="index.php">Dashboard</a>
        <a href="users.php" class="active">Benutzer</a>
        <a href="teams.php">Teams</a>
        <a href="moderation.php">Moderation</a>
        <a href="settings.php">Einstellungen</a>
    </nav>
    
    <div class="container">
        <div class="card">
            <h2>Benutzerverwaltung</h2>
            <p>Verwalte alle registrierten Benutzer, deren Rollen und Status.</p>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="card">
            <table>
                <thead>
                    <tr>
                        <th>Benutzer</th>
                        <th>Rolle</th>
                        <th>Status</th>
                        <th>Registriert</th>
                        <th>Aktionen</th>
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
                                        <div class="user-avatar" style="background: #5865f2; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold;">
                                            <?= strtoupper(substr($user['username'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($user['username']) ?></strong><br>
                                        <small style="color: #72767d;">ID: <?= $user['discord_id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge" style="background-color: <?= $user['role_color'] ?? '#5865f2' ?>;">
                                    <?= htmlspecialchars($user['role_name'] ?? 'Keine Rolle') ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $user['status'] ?>">
                                    <?= ucfirst($user['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></td>
                            <td>
                                <?php if ($permissions->canManageUser($_SESSION['user_id'], $user['id'])): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <select name="role_id" onchange="this.form.submit()">
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
                                        <select name="status" onchange="this.form.submit()">
                                            <option value="active" <?= $user['status'] == 'active' ? 'selected' : '' ?>>Aktiv</option>
                                            <option value="trial" <?= $user['status'] == 'trial' ? 'selected' : '' ?>>Trial</option>
                                            <option value="inactive" <?= $user['status'] == 'inactive' ? 'selected' : '' ?>>Inaktiv</option>
                                            <option value="banned" <?= $user['status'] == 'banned' ? 'selected' : '' ?>>Gebannt</option>
                                        </select>
                                    </form>
                                <?php else: ?>
                                    <span style="color: #72767d;">Keine Berechtigung</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <a href="?page=<?= $i ?>" class="<?= $i == $page ? 'active' : '' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>