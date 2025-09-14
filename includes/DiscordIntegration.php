<?php
// includes/DiscordIntegration.php

class DiscordIntegration {
    private $botToken;
    private $guildId;
    private $db;
    private $baseUrl = 'https://discord.com/api/v10';
    
    public function __construct($config, $database) {
        $this->botToken = $config['discord']['token'];
        $this->guildId = $config['discord']['guild_id'];
        $this->db = $database;
    }
    
    /**
     * Macht Discord API Request mit Bot Token
     */
    private function makeRequest($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;
        
        $headers = [
            'Authorization: Bot ' . $this->botToken,
            'Content-Type: application/json',
            'User-Agent: V3NTOM-Framework/1.0'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        }
        
        error_log("Discord API Error: HTTP $httpCode - $response");
        return false;
    }
    
    /**
     * Importiert alle Discord-Rollen und speichert sie in der Datenbank
     */
    public function importDiscordRoles() {
        $roles = $this->makeRequest("/guilds/{$this->guildId}/roles");
        
        if (!$roles) {
            throw new Exception("Fehler beim Abrufen der Discord-Rollen");
        }
        
        foreach ($roles as $role) {
            // @everyone Rolle überspringen
            if ($role['name'] === '@everyone') continue;
            
            // Prüfen ob Rolle bereits existiert
            $existing = $this->db->fetchOne($this->db->query(
                "SELECT id FROM discord_roles WHERE discord_role_id = ?", 
                [$role['id']]
            ));
            
            $roleData = [
                'discord_role_id' => $role['id'],
                'name' => $role['name'],
                'color' => $role['color'],
                'position' => $role['position'],
                'permissions' => $role['permissions'],
                'mentionable' => $role['mentionable'] ? 1 : 0,
                'hoist' => $role['hoist'] ? 1 : 0,
                'managed' => $role['managed'] ? 1 : 0,
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($existing) {
                // Rolle aktualisieren
                $this->db->update('discord_roles', $roleData, 'discord_role_id = ?', [$role['id']]);
            } else {
                // Neue Rolle einfügen
                $roleData['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('discord_roles', $roleData);
            }
        }
        
        return count($roles);
    }
    
    /**
     * Importiert alle Discord-Benutzer und aktualisiert die Datenbank
     */
    public function importDiscordMembers() {
        $members = $this->makeRequest("/guilds/{$this->guildId}/members?limit=1000");
        
        if (!$members) {
            throw new Exception("Fehler beim Abrufen der Discord-Mitglieder");
        }
        
        $importedCount = 0;
        
        foreach ($members as $member) {
            $user = $member['user'];
            
            // Prüfen ob User bereits existiert
            $existingUser = $this->db->fetchOne($this->db->query(
                "SELECT id FROM users WHERE discord_id = ?", 
                [$user['id']]
            ));
            
            $userData = [
                'discord_id' => $user['id'],
                'username' => $user['username'],
                'discriminator' => $user['discriminator'] ?? '0',
                'avatar' => $user['avatar'],
                'bot' => isset($user['bot']) && $user['bot'] ? 1 : 0,
                'nick' => $member['nick'],
                'joined_at' => $member['joined_at'],
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            if ($existingUser) {
                // Benutzer aktualisieren
                $this->db->update('users', $userData, 'discord_id = ?', [$user['id']]);
                $userId = $existingUser['id'];
            } else {
                // Neuen Benutzer erstellen
                $userData['created_at'] = date('Y-m-d H:i:s');
                $userData['status'] = 'active';
                $userId = $this->db->insert('users', $userData);
                $importedCount++;
            }
            
            // Discord-Rollen des Benutzers synchronisieren
            $this->syncUserDiscordRoles($userId, $member['roles']);
        }
        
        return $importedCount;
    }
    
    /**
     * Synchronisiert Discord-Rollen eines Benutzers
     */
    public function syncUserDiscordRoles($userId, $discordRoleIds) {
        // Alte Rollen-Zuordnungen löschen
        $this->db->query("DELETE FROM user_discord_roles WHERE user_id = ?", [$userId]);
        
        // Neue Rollen-Zuordnungen einfügen
        foreach ($discordRoleIds as $roleId) {
            $this->db->insert('user_discord_roles', [
                'user_id' => $userId,
                'discord_role_id' => $roleId,
                'assigned_at' => date('Y-m-d H:i:s')
            ]);
        }
    }
    
    /**
     * Erstellt eine neue Discord-Rolle
     */
    public function createDiscordRole($name, $color = 0, $permissions = 0, $hoist = false, $mentionable = false) {
        $data = [
            'name' => $name,
            'color' => $color,
            'permissions' => (string)$permissions,
            'hoist' => $hoist,
            'mentionable' => $mentionable
        ];
        
        $role = $this->makeRequest("/guilds/{$this->guildId}/roles", 'POST', $data);
        
        if ($role) {
            // Rolle in Datenbank speichern
            $this->db->insert('discord_roles', [
                'discord_role_id' => $role['id'],
                'name' => $role['name'],
                'color' => $role['color'],
                'position' => $role['position'],
                'permissions' => $role['permissions'],
                'mentionable' => $role['mentionable'] ? 1 : 0,
                'hoist' => $role['hoist'] ? 1 : 0,
                'managed' => $role['managed'] ? 1 : 0,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        return $role;
    }
    
    /**
     * Weist einem Benutzer eine Discord-Rolle zu
     */
    public function assignRoleToUser($userId, $discordRoleId, $reason = null) {
        // Discord User ID aus Datenbank holen
        $user = $this->db->fetchOne($this->db->query(
            "SELECT discord_id FROM users WHERE id = ?", 
            [$userId]
        ));
        
        if (!$user) {
            throw new Exception("Benutzer nicht gefunden");
        }
        
        $endpoint = "/guilds/{$this->guildId}/members/{$user['discord_id']}/roles/{$discordRoleId}";
        $headers = [];
        
        if ($reason) {
            $headers['X-Audit-Log-Reason'] = $reason;
        }
        
        $success = $this->makeRequest($endpoint, 'PUT');
        
        if ($success !== false) {
            // In Datenbank eintragen
            $this->db->insert('user_discord_roles', [
                'user_id' => $userId,
                'discord_role_id' => $discordRoleId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_reason' => $reason
            ]);
        }
        
        return $success !== false;
    }
    
    /**
     * Entfernt eine Discord-Rolle von einem Benutzer
     */
    public function removeRoleFromUser($userId, $discordRoleId, $reason = null) {
        $user = $this->db->fetchOne($this->db->query(
            "SELECT discord_id FROM users WHERE id = ?", 
            [$userId]
        ));
        
        if (!$user) {
            throw new Exception("Benutzer nicht gefunden");
        }
        
        $endpoint = "/guilds/{$this->guildId}/members/{$user['discord_id']}/roles/{$discordRoleId}";
        $success = $this->makeRequest($endpoint, 'DELETE');
        
        if ($success !== false) {
            // Aus Datenbank entfernen
            $this->db->query(
                "DELETE FROM user_discord_roles WHERE user_id = ? AND discord_role_id = ?", 
                [$userId, $discordRoleId]
            );
        }
        
        return $success !== false;
    }
    
    /**
     * Holt Channel-Nachrichten für Ankündigungen
     */
    public function getAnnouncementsFromChannel($channelId, $limit = 50) {
        $messages = $this->makeRequest("/channels/{$channelId}/messages?limit={$limit}");
        
        if (!$messages) {
            return [];
        }
        
        $announcements = [];
        foreach ($messages as $message) {
            $announcements[] = [
                'id' => $message['id'],
                'content' => $message['content'],
                'author' => $message['author']['username'],
                'author_id' => $message['author']['id'],
                'timestamp' => $message['timestamp'],
                'embeds' => $message['embeds'],
                'attachments' => $message['attachments']
            ];
        }
        
        return $announcements;
    }
    
    /**
     * Sendet eine Nachricht in einen Discord-Channel
     */
    public function sendMessage($channelId, $content, $embeds = null) {
        $data = ['content' => $content];
        
        if ($embeds) {
            $data['embeds'] = $embeds;
        }
        
        return $this->makeRequest("/channels/{$channelId}/messages", 'POST', $data);
    }
    
    /**
     * Erstellt Team-Ankündigung in Discord
     */
    public function announceNewTeam($teamData, $channelId) {
        $embed = [
            'title' => '🆕 Neues Team erstellt!',
            'description' => "Das Team **{$teamData['name']}** wurde erstellt.",
            'color' => 5865242, // Discord Blau
            'fields' => [
                [
                    'name' => '🎮 Spiel',
                    'value' => $teamData['game'],
                    'inline' => true
                ],
                [
                    'name' => '👤 Teamleiter',
                    'value' => "<@{$teamData['leader_discord_id']}>",
                    'inline' => true
                ],
                [
                    'name' => '👥 Max. Mitglieder',
                    'value' => $teamData['max_members'],
                    'inline' => true
                ]
            ],
            'timestamp' => date('c'),
            'footer' => [
                'text' => 'V3NTOM E-Sport Framework'
            ]
        ];
        
        if (!empty($teamData['description'])) {
            $embed['fields'][] = [
                'name' => '📝 Beschreibung',
                'value' => substr($teamData['description'], 0, 1000),
                'inline' => false
            ];
        }
        
        return $this->sendMessage($channelId, '', [$embed]);
    }
    
    /**
     * Prüft Bot-Berechtigungen im Server
     */
    public function checkBotPermissions() {
        $botMember = $this->makeRequest("/guilds/{$this->guildId}/members/@me");
        
        if (!$botMember) {
            return false;
        }
        
        $permissions = [
            'MANAGE_ROLES' => 268435456,
            'MANAGE_CHANNELS' => 16,
            'KICK_MEMBERS' => 2,
            'BAN_MEMBERS' => 4,
            'SEND_MESSAGES' => 2048,
            'EMBED_LINKS' => 16384
        ];
        
        $botPermissions = 0;
        foreach ($botMember['roles'] as $roleId) {
            $role = $this->makeRequest("/guilds/{$this->guildId}/roles");
            foreach ($role as $r) {
                if ($r['id'] === $roleId) {
                    $botPermissions |= $r['permissions'];
                }
            }
        }
        
        $missingPermissions = [];
        foreach ($permissions as $name => $value) {
            if (($botPermissions & $value) === 0) {
                $missingPermissions[] = $name;
            }
        }
        
        return [
            'hasAllPermissions' => empty($missingPermissions),
            'missing' => $missingPermissions,
            'current' => $botPermissions
        ];
    }
}
?>