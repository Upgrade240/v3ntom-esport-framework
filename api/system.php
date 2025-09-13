<?php
session_start();
header('Content-Type: application/json');

require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';
require_once '../includes/PermissionManager.php';

// Security checks
if (!Security::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$config = include '../config.php';
$db = new Database($config);
$permissions = new PermissionManager($db);

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'clear_cache':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            // Clear session cache
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            
            // Clear file cache if exists
            $cacheDir = '../cache/';
            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }
            
            // Clear permissions cache
            $permissions->clearPermissionCache();
            
            echo json_encode(['success' => true, 'message' => 'Cache successfully cleared']);
            break;
            
        case 'get_stats':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            $stats = [
                'users' => [
                    'total' => $db->fetch("SELECT COUNT(*) as count FROM users")['count'],
                    'active' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'active'")['count'],
                    'trial' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'trial'")['count'],
                    'banned' => $db->fetch("SELECT COUNT(*) as count FROM users WHERE status = 'banned'")['count']
                ],
                'teams' => [
                    'total' => $db->fetch("SELECT COUNT(*) as count FROM teams")['count'],
                    'recruiting' => $db->fetch("SELECT COUNT(*) as count FROM teams WHERE recruitment_open = 1")['count']
                ],
                'applications' => [
                    'pending' => $db->fetch("SELECT COUNT(*) as count FROM team_applications WHERE status = 'pending'")['count'],
                    'approved' => $db->fetch("SELECT COUNT(*) as count FROM team_applications WHERE status = 'approved'")['count']
                ],
                'events' => [
                    'upcoming' => $db->fetch("SELECT COUNT(*) as count FROM events WHERE start_date > NOW()")['count'] ?? 0,
                    'total' => $db->fetch("SELECT COUNT(*) as count FROM events")['count'] ?? 0
                ],
                'tickets' => [
                    'open' => $db->fetch("SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'in_progress')")['count'] ?? 0,
                    'total' => $db->fetch("SELECT COUNT(*) as count FROM support_tickets")['count'] ?? 0
                ]
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        case 'get_activity':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            $limit = (int)($input['limit'] ?? 10);
            
            $activity = $db->fetchAll("
                SELECT 'user_joined' as type, u.username, u.created_at as timestamp, 'Neuer Benutzer registriert' as description
                FROM users u 
                WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                SELECT 'team_created' as type, u.username, t.created_at as timestamp, CONCAT('Team \"', t.name, '\" erstellt') as description
                FROM teams t
                LEFT JOIN users u ON t.leader_id = u.id
                WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                UNION ALL
                
                SELECT 'application_submitted' as type, u.username, ta.created_at as timestamp, 'Neue Team-Bewerbung eingereicht' as description
                FROM team_applications ta
                JOIN users u ON ta.user_id = u.id
                WHERE ta.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                
                ORDER BY timestamp DESC 
                LIMIT ?
            ", [$limit]);
            
            echo json_encode(['success' => true, 'data' => $activity]);
            break;
            
        case 'update_setting':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            $key = $input['key'] ?? '';
            $value = $input['value'] ?? '';
            
            if (empty($key)) {
                throw new Exception('Setting key is required');
            }
            
            $result = $db->update('system_settings', 
                                ['value' => $value, 'updated_by' => $_SESSION['user_id']], 
                                '`key` = ?', [$key]);
            
            if ($result === 0) {
                throw new Exception('Setting not found or no changes made');
            }
            
            echo json_encode(['success' => true, 'message' => 'Setting updated successfully']);
            break;
            
        case 'get_system_info':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            $info = [
                'php_version' => PHP_VERSION,
                'mysql_version' => $db->fetch("SELECT VERSION() as version")['version'],
                'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'memory_limit' => ini_get('memory_limit'),
                'max_execution_time' => ini_get('max_execution_time'),
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'disk_free' => function_exists('disk_free_space') ? disk_free_space('.') : null,
                'disk_total' => function_exists('disk_total_space') ? disk_total_space('.') : null
            ];
            
            echo json_encode(['success' => true, 'data' => $info]);
            break;
            
        case 'test_discord_connection':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            $botToken = $config['discord']['token'];
            $guildId = $config['discord']['guild_id'];
            
            if (empty($botToken)) {
                throw new Exception('Discord bot token not configured');
            }
            
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_URL => "https://discord.com/api/v10/guilds/$guildId",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    "Authorization: Bot $botToken",
                    'Content-Type: application/json'
                ],
                CURLOPT_TIMEOUT => 10
            ]);
            
            $response = curl_exec($curl);
            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            
            if ($httpCode === 200) {
                $guildData = json_decode($response, true);
                echo json_encode([
                    'success' => true, 
                    'message' => 'Discord connection successful',
                    'data' => [
                        'guild_name' => $guildData['name'] ?? 'Unknown',
                        'member_count' => $guildData['approximate_member_count'] ?? 0
                    ]
                ]);
            } else {
                throw new Exception('Discord API returned HTTP ' . $httpCode . ': ' . $response);
            }
            break;
            
        case 'backup_database':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.settings')) {
                throw new Exception('Insufficient permissions');
            }
            
            $dbName = $config['database']['name'];
            $dbUser = $config['database']['user'];
            $dbPass = $config['database']['pass'];
            $dbHost = $config['database']['host'];
            
            $backupDir = '../backups/';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . $filename;
            
            $command = "mysqldump -h$dbHost -u$dbUser -p$dbPass $dbName > $filepath";
            $output = shell_exec($command . ' 2>&1');
            
            if (file_exists($filepath) && filesize($filepath) > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Database backup created successfully',
                    'filename' => $filename
                ]);
            } else {
                throw new Exception('Backup failed: ' . $output);
            }
            break;
            
        case 'get_moderation_stats':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'members.warn')) {
                throw new Exception('Insufficient permissions');
            }
            
            $stats = [
                'total_actions' => $db->fetch("SELECT COUNT(*) as count FROM moderation_logs")['count'],
                'warns' => $db->fetch("SELECT COUNT(*) as count FROM moderation_logs WHERE action_type = 'warn'")['count'],
                'bans' => $db->fetch("SELECT COUNT(*) as count FROM moderation_logs WHERE action_type = 'ban'")['count'],
                'kicks' => $db->fetch("SELECT COUNT(*) as count FROM moderation_logs WHERE action_type = 'kick'")['count'],
                'recent' => $db->fetch("SELECT COUNT(*) as count FROM moderation_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['count']
            ];
            
            echo json_encode(['success' => true, 'data' => $stats]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>