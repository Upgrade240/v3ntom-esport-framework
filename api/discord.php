<?php
// api/discord.php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';
require_once '../includes/Security.php';

// Temporäre Mock-Klassen bis die Integration vollständig ist
if (!class_exists('DiscordIntegration')) {
    class DiscordIntegration {
        public function __construct($config, $db) {}
        public function importDiscordRoles() { return 5; }
        public function importDiscordMembers() { return 10; }
        public function checkBotPermissions() { 
            return ['hasAllPermissions' => true, 'missing' => []]; 
        }
        public function assignRoleToUser($userId, $roleId, $reason) { return true; }
        public function removeRoleFromUser($userId, $roleId, $reason) { return true; }
    }
}

if (!class_exists('TeamManager')) {
    class TeamManager {
        public function __construct($db, $discord, $config) {}
        public function createTeam($data, $leaderId) {
            return ['success' => true, 'team_id' => 1, 'discord_role_id' => '123456789'];
        }
        public function hasTeamPermission($userId, $teamId, $permission) { return true; }
        public function assignTeamRoleToMember($memberId, $roleId, $assignedBy) { return true; }
        public function syncTeamWithDiscord($teamId) {
            return ['synced_members' => 5, 'total_members' => 5, 'errors' => []];
        }
        public function getTeamMembersWithRoles($teamId) { return []; }
    }
}

if (!class_exists('AnnouncementManager')) {
    class AnnouncementManager {
        public function __construct($db, $discord, $config) {}
        public function importDiscordAnnouncements($channelId, $limit) { return 3; }
        public function getAnnouncementsForWebsite($limit, $type) { return []; }
        public function createAnnouncement($data) {
            return ['id' => '987654321', 'content' => 'Test Ankündigung'];
        }
        public function syncAllAnnouncementChannels() { return 5; }
    }
}

require_once '../includes/PermissionManager.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight-Request behandeln
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $config = include '../config.php';
    $db = new Database($config);
    $discord = new DiscordIntegration($config, $db);
    $teamManager = new TeamManager($db, $discord, $config);
    $announcementManager = new AnnouncementManager($db, $discord, $config);
    $permissions = new PermissionManager($db);
    
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    $method = $_SERVER['REQUEST_METHOD'];
    
    // Authentifizierung für alle Aktionen außer Status-Check
    if ($action !== 'status') {
        Security::checkAuth();
    }
    
    switch ($action) {
        case 'sync-roles':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung für Discord-Synchronisation");
            }
            
            $imported = $discord->importDiscordRoles();
            echo json_encode([
                'success' => true,
                'imported_roles' => $imported,
                'message' => "Erfolgreich {$imported} Discord-Rollen importiert"
            ]);
            break;
            
        case 'sync-members':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung für Discord-Synchronisation");
            }
            
            $imported = $discord->importDiscordMembers();
            echo json_encode([
                'success' => true,
                'imported_members' => $imported,
                'message' => "Erfolgreich {$imported} neue Discord-Mitglieder importiert"
            ]);
            break;
            
        case 'sync-announcements':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung für Discord-Synchronisation");
            }
            
            $channelId = $_POST['channel_id'] ?? null;
            $limit = (int)($_POST['limit'] ?? 50);
            
            $imported = $announcementManager->importDiscordAnnouncements($channelId, $limit);
            echo json_encode([
                'success' => true,
                'imported_announcements' => $imported,
                'message' => "Erfolgreich {$imported} Ankündigungen importiert"
            ]);
            break;
            
        case 'assign-role':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $userId = (int)$input['user_id'];
            $discordRoleId = $input['discord_role_id'];
            $reason = $input['reason'] ?? null;
            
            if (!$permissions->canManageUser($_SESSION['user_id'], $userId)) {
                throw new Exception("Keine Berechtigung zum Verwalten dieses Benutzers");
            }
            
            $success = $discord->assignRoleToUser($userId, $discordRoleId, $reason);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Discord-Rolle erfolgreich zugewiesen' : 'Fehler beim Zuweisen der Rolle'
            ]);
            break;
            
        case 'remove-role':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $userId = (int)$input['user_id'];
            $discordRoleId = $input['discord_role_id'];
            $reason = $input['reason'] ?? null;
            
            if (!$permissions->canManageUser($_SESSION['user_id'], $userId)) {
                throw new Exception("Keine Berechtigung zum Verwalten dieses Benutzers");
            }
            
            $success = $discord->removeRoleFromUser($userId, $discordRoleId, $reason);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Discord-Rolle erfolgreich entfernt' : 'Fehler beim Entfernen der Rolle'
            ]);
            break;
            
        case 'create-team':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            if (!$permissions->hasPermission($_SESSION['user_id'], 'teams.create')) {
                throw new Exception("Keine Berechtigung zum Erstellen von Teams");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $teamData = [
                'name' => $input['name'],
                'game' => $input['game'],
                'description' => $input['description'] ?? '',
                'max_members' => (int)($input['max_members'] ?? 5),
                'recruitment_open' => $input['recruitment_open'] ?? 1
            ];
            
            $result = $teamManager->createTeam($teamData, $_SESSION['user_id']);
            echo json_encode($result);
            break;
            
        case 'assign-team-role':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $memberId = (int)$input['member_id'];
            $teamRoleId = (int)$input['team_role_id'];
            $teamId = (int)$input['team_id'];
            
            if (!$teamManager->hasTeamPermission($_SESSION['user_id'], $teamId, 'can_assign_roles')) {
                throw new Exception("Keine Berechtigung zum Zuweisen von Team-Rollen");
            }
            
            $success = $teamManager->assignTeamRoleToMember($memberId, $teamRoleId, $_SESSION['user_id']);
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Team-Rolle erfolgreich zugewiesen' : 'Team-Rolle bereits vorhanden'
            ]);
            break;
            
        case 'sync-team':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $teamId = (int)$input['team_id'];
            
            if (!$teamManager->hasTeamPermission($_SESSION['user_id'], $teamId, 'can_manage_members')) {
                throw new Exception("Keine Berechtigung zum Synchronisieren des Teams");
            }
            
            $result = $teamManager->syncTeamWithDiscord($teamId);
            echo json_encode([
                'success' => true,
                'synced_members' => $result['synced_members'],
                'total_members' => $result['total_members'],
                'errors' => $result['errors'],
                'message' => "Team-Synchronisation abgeschlossen: {$result['synced_members']}/{$result['total_members']} Mitglieder"
            ]);
            break;
            
        case 'get-announcements':
            $limit = (int)($_GET['limit'] ?? 10);
            $type = $_GET['type'] ?? null;
            
            $announcements = $announcementManager->getAnnouncementsForWebsite($limit, $type);
            echo json_encode([
                'success' => true,
                'announcements' => $announcements
            ]);
            break;
            
        case 'create-announcement':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.announcements')) {
                throw new Exception("Keine Berechtigung zum Erstellen von Ankündigungen");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            $announcementData = [
                'title' => $input['title'],
                'description' => $input['description'] ?? '',
                'content' => $input['content'] ?? '',
                'type' => $input['type'] ?? 'general',
                'related_id' => $input['related_id'] ?? null,
                'author_discord_id' => $_SESSION['discord_id'] ?? null,
                'author_name' => $_SESSION['username'] ?? 'System'
            ];
            
            if (!empty($input['fields'])) {
                $announcementData['fields'] = is_string($input['fields']) 
                    ? json_decode($input['fields'], true) 
                    : $input['fields'];
            }
            
            $message = $announcementManager->createAnnouncement($announcementData);
            echo json_encode([
                'success' => (bool)$message,
                'message_id' => $message['id'] ?? null,
                'message' => $message ? 'Ankündigung erfolgreich erstellt' : 'Fehler beim Erstellen der Ankündigung'
            ]);
            break;
            
        case 'get-discord-roles':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung zum Anzeigen von Discord-Rollen");
            }
            
            // Mock-Daten für Demo - in echter Implementierung aus discord_roles Tabelle laden
            $roles = [
                [
                    'id' => 1,
                    'discord_role_id' => '123456789',
                    'name' => 'Admin',
                    'color' => 16711680,
                    'position' => 10,
                    'assigned_users' => 3,
                    'team_name' => null
                ],
                [
                    'id' => 2,
                    'discord_role_id' => '987654321',
                    'name' => 'Moderator',
                    'color' => 255,
                    'position' => 5,
                    'assigned_users' => 7,
                    'team_name' => null
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'roles' => $roles
            ]);
            break;
            
        case 'get-user-roles':
            $userId = (int)($_GET['user_id'] ?? $_SESSION['user_id']);
            
            if ($userId !== $_SESSION['user_id'] && !$permissions->canManageUser($_SESSION['user_id'], $userId)) {
                throw new Exception("Keine Berechtigung zum Anzeigen der Benutzer-Rollen");
            }
            
            // Mock-Daten
            $roles = [
                [
                    'discord_role_id' => '123456789',
                    'name' => 'Member',
                    'color' => 5865242,
                    'assigned_at' => date('Y-m-d H:i:s'),
                    'assigned_reason' => 'Automatische Zuweisung',
                    'assigned_by_username' => 'System'
                ]
            ];
            
            echo json_encode([
                'success' => true,
                'user_id' => $userId,
                'roles' => $roles
            ]);
            break;
            
        case 'get-team-members':
            $teamId = (int)$_GET['team_id'];
            
            $members = $teamManager->getTeamMembersWithRoles($teamId);
            echo json_encode([
                'success' => true,
                'team_id' => $teamId,
                'members' => $members
            ]);
            break;
            
        case 'check-bot-permissions':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung zum Prüfen der Bot-Berechtigungen");
            }
            
            $permissionsCheck = $discord->checkBotPermissions();
            echo json_encode([
                'success' => true,
                'permissions' => $permissionsCheck
            ]);
            break;
            
        case 'status':
            $status = [
                'discord_integration' => true,
                'bot_online' => true,
                'last_sync' => date('Y-m-d H:i:s')
            ];
            
            echo json_encode([
                'success' => true,
                'status' => $status
            ]);
            break;
            
        case 'full-sync':
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung für vollständige Discord-Synchronisation");
            }
            
            $results = [
                'roles' => 0,
                'members' => 0,
                'announcements' => 0,
                'errors' => []
            ];
            
            try {
                $results['roles'] = $discord->importDiscordRoles();
                $results['members'] = $discord->importDiscordMembers();
                $results['announcements'] = $announcementManager->syncAllAnnouncementChannels();
            } catch (Exception $e) {
                $results['errors'][] = $e->getMessage();
            }
            
            echo json_encode([
                'success' => empty($results['errors']),
                'results' => $results,
                'message' => empty($results['errors']) 
                    ? 'Vollständige Synchronisation erfolgreich abgeschlossen'
                    : 'Synchronisation mit Fehlern abgeschlossen'
            ]);
            break;
            
        case 'create-role':
            if ($method !== 'POST') {
                throw new Exception("POST-Request erforderlich");
            }
            
            if (!$permissions->hasPermission($_SESSION['user_id'], 'admin.discord')) {
                throw new Exception("Keine Berechtigung zum Erstellen von Discord-Rollen");
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            if (!$input) {
                $input = $_POST;
            }
            
            // Mock: Discord-Rolle würde hier erstellt werden
            $roleData = [
                'name' => $input['name'],
                'color' => $input['color'] ?? 0,
                'hoist' => $input['hoist'] ?? false,
                'mentionable' => $input['mentionable'] ?? false
            ];
            
            echo json_encode([
                'success' => true,
                'discord_role_id' => '999888777',
                'message' => "Discord-Rolle '{$roleData['name']}' erfolgreich erstellt"
            ]);
            break;
            
        default:
            throw new Exception("Unbekannte Aktion: " . $action);
    }
    
} catch (Exception $e) {
    error_log("Discord API Error: " . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>