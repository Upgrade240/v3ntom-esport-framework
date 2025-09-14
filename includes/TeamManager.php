<?php
// includes/TeamManager.php

class TeamManager {
    private $db;
    private $discord;
    private $config;
    
    public function __construct($database, $discordIntegration, $config) {
        $this->db = $database;
        $this->discord = $discordIntegration;
        $this->config = $config;
    }
    
    /**
     * Erstellt ein neues Team mit automatischer Discord-Integration
     */
    public function createTeam($teamData, $leaderId) {
        $this->db->beginTransaction();
        
        try {
            // Team in Datenbank erstellen
            $teamId = $this->db->insert('teams', [
                'name' => $teamData['name'],
                'game' => $teamData['game'],
                'description' => $teamData['description'] ?? null,
                'leader_id' => $leaderId,
                'max_members' => $teamData['max_members'] ?? 5,
                'recruitment_open' => $teamData['recruitment_open'] ?? 1,
                'auto_role_assignment' => 1,
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            // Discord-Rolle für das Team erstellen
            $roleColor = $this->generateTeamColor($teamData['name']);
            $discordRole = $this->discord->createDiscordRole(
                "Team: " . $teamData['name'],
                $roleColor,
                0, // Keine besonderen Berechtigungen
                true, // Separat in Mitgliederliste anzeigen
                true  // Erwähnbar
            );
            
            if (!$discordRole) {
                throw new Exception("Discord-Rolle konnte nicht erstellt werden");
            }
            
            // Discord-Rolle-ID in Team speichern
            $this->db->update('teams', 
                ['discord_role_id' => $discordRole['id']], 
                'id = ?', 
                [$teamId]
            );
            
            // Standard Team-Rollen erstellen
            $this->createDefaultTeamRoles($teamId, $discordRole['id']);
            
            // Teamleiter zum Team hinzufügen
            $this->addMemberToTeam($teamId, $leaderId, 'leader');
            
            // Discord-Ankündigung senden
            if ($this->config['discord']['announcement_channel']) {
                $leaderData = $this->db->fetchOne($this->db->query(
                    "SELECT discord_id FROM users WHERE id = ?", 
                    [$leaderId]
                ));
                
                $announcementData = array_merge($teamData, [
                    'leader_discord_id' => $leaderData['discord_id']
                ]);
                
                $this->discord->announceNewTeam(
                    $announcementData, 
                    $this->config['discord']['announcement_channel']
                );
                
                // Ankündigung als gesendet markieren
                $this->db->update('teams', 
                    ['discord_announcement_sent' => 1], 
                    'id = ?', 
                    [$teamId]
                );
            }
            
            $this->db->commit();
            
            return [
                'success' => true,
                'team_id' => $teamId,
                'discord_role_id' => $discordRole['id'],
                'message' => 'Team erfolgreich erstellt und Discord-Integration aktiviert'
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            error_log("Team creation failed: " . $e->getMessage());
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Erstellt Standard-Rollen für ein neues Team
     */
    private function createDefaultTeamRoles($teamId, $mainDiscordRoleId) {
        $defaultRoles = [
            [
                'name' => 'Teamleiter',
                'color' => '#E74C3C',
                'hierarchy_level' => 100,
                'can_manage_members' => 1,
                'can_assign_roles' => 1,
                'can_kick_members' => 1
            ],
            [
                'name' => 'Vize-Captain',
                'color' => '#F39C12',
                'hierarchy_level' => 80,
                'can_manage_members' => 1,
                'can_assign_roles' => 1,
                'can_kick_members' => 0
            ],
            [
                'name' => 'Spieler',
                'color' => '#2ECC71',
                'hierarchy_level' => 50,
                'can_manage_members' => 0,
                'can_assign_roles' => 0,
                'can_kick_members' => 0
            ],
            [
                'name' => 'Trial Spieler',
                'color' => '#3498DB',
                'hierarchy_level' => 20,
                'can_manage_members' => 0,
                'can_assign_roles' => 0,
                'can_kick_members' => 0
            ],
            [
                'name' => 'Ersatzspieler',
                'color' => '#95A5A6',
                'hierarchy_level' => 10,
                'can_manage_members' => 0,
                'can_assign_roles' => 0,
                'can_kick_members' => 0
            ]
        ];
        
        foreach ($defaultRoles as $roleData) {
            $roleData['team_id'] = $teamId;
            $roleData['created_at'] = date('Y-m-d H:i:s');
            
            // Für Teamleiter die Haupt-Discord-Rolle verwenden
            if ($roleData['name'] === 'Teamleiter') {
                $roleData['discord_role_id'] = $mainDiscordRoleId;
            }
            
            $this->db->insert('team_roles', $roleData);
        }
    }
    
    /**
     * Fügt ein Mitglied zum Team hinzu mit automatischer Rollen-Zuweisung
     */
    public function addMemberToTeam($teamId, $userId, $roleType = 'player') {
        // Team-Daten laden
        $team = $this->db->fetchOne($this->db->query(
            "SELECT * FROM teams WHERE id = ?", 
            [$teamId]
        ));
        
        if (!$team) {
            throw new Exception("Team nicht gefunden");
        }
        
        // Prüfen ob bereits Mitglied
        $existing = $this->db->fetchOne($this->db->query(
            "SELECT id FROM team_members WHERE team_id = ? AND user_id = ?", 
            [$teamId, $userId]
        ));
        
        if ($existing) {
            throw new Exception("Benutzer ist bereits Mitglied des Teams");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Team-Mitglied hinzufügen
            $memberId = $this->db->insert('team_members', [
                'team_id' => $teamId,
                'user_id' => $userId,
                'role' => $roleType,
                'joined_at' => date('Y-m-d H:i:s')
            ]);
            
            // Entsprechende Team-Rolle zuweisen
            $teamRole = $this->getTeamRoleByType($teamId, $roleType);
            if ($teamRole) {
                $this->assignTeamRoleToMember($memberId, $teamRole['id']);
            }
            
            // Discord-Rolle zuweisen falls Auto-Assignment aktiviert
            if ($team['auto_role_assignment'] && $team['discord_role_id']) {
                $this->discord->assignRoleToUser(
                    $userId, 
                    $team['discord_role_id'], 
                    "Automatische Team-Aufnahme"
                );
            }
            
            $this->db->commit();
            return $memberId;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Weist einem Team-Mitglied eine spezifische Team-Rolle zu
     */
    public function assignTeamRoleToMember($memberId, $teamRoleId, $assignedBy = null) {
        // Prüfen ob Rolle bereits zugewiesen
        $existing = $this->db->fetchOne($this->db->query(
            "SELECT id FROM team_member_roles WHERE team_member_id = ? AND team_role_id = ?",
            [$memberId, $teamRoleId]
        ));
        
        if ($existing) {
            return false; // Bereits zugewiesen
        }
        
        // Team-Rolle-Daten laden
        $teamRole = $this->db->fetchOne($this->db->query(
            "SELECT tr.*, t.discord_role_id as team_discord_role_id 
             FROM team_roles tr 
             JOIN teams t ON tr.team_id = t.id 
             WHERE tr.id = ?",
            [$teamRoleId]
        ));
        
        if (!$teamRole) {
            throw new Exception("Team-Rolle nicht gefunden");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Team-Rolle zuweisen
            $this->db->insert('team_member_roles', [
                'team_member_id' => $memberId,
                'team_role_id' => $teamRoleId,
                'assigned_at' => date('Y-m-d H:i:s'),
                'assigned_by' => $assignedBy
            ]);
            
            // Wenn Team-Rolle eine Discord-Rolle hat, diese auch zuweisen
            if ($teamRole['discord_role_id']) {
                $member = $this->db->fetchOne($this->db->query(
                    "SELECT user_id FROM team_members WHERE id = ?",
                    [$memberId]
                ));
                
                $this->discord->assignRoleToUser(
                    $member['user_id'], 
                    $teamRole['discord_role_id'],
                    "Team-Rolle zugewiesen: " . $teamRole['name']
                );
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Prüft ob ein Benutzer eine bestimmte Team-Berechtigung hat
     */
    public function hasTeamPermission($userId, $teamId, $permission) {
        $sql = "
            SELECT COUNT(*) as count
            FROM team_members tm
            JOIN team_member_roles tmr ON tm.id = tmr.team_member_id
            JOIN team_roles tr ON tmr.team_role_id = tr.id
            WHERE tm.user_id = ? 
            AND tm.team_id = ? 
            AND tr.{$permission} = 1
        ";
        
        $result = $this->db->fetchOne($this->db->query($sql, [$userId, $teamId]));
        return $result['count'] > 0;
    }
    
    /**
     * Holt alle Team-Mitglieder mit ihren Rollen
     */
    public function getTeamMembersWithRoles($teamId) {
        $sql = "
            SELECT 
                tm.*,
                u.username,
                u.discord_id,
                u.avatar,
                GROUP_CONCAT(tr.name) as team_roles,
                GROUP_CONCAT(tr.color) as role_colors,
                MAX(tr.hierarchy_level) as max_hierarchy
            FROM team_members tm
            JOIN users u ON tm.user_id = u.id
            LEFT JOIN team_member_roles tmr ON tm.id = tmr.team_member_id
            LEFT JOIN team_roles tr ON tmr.team_role_id = tr.id
            WHERE tm.team_id = ?
            GROUP BY tm.id
            ORDER BY max_hierarchy DESC, tm.joined_at ASC
        ";
        
        $result = $this->db->query($sql, [$teamId]);
        return $this->db->fetchAll($result);
    }
    
    /**
     * Entfernt ein Mitglied aus dem Team
     */
    public function removeMemberFromTeam($teamId, $userId, $reason = null) {
        $team = $this->db->fetchOne($this->db->query(
            "SELECT * FROM teams WHERE id = ?", 
            [$teamId]
        ));
        
        $member = $this->db->fetchOne($this->db->query(
            "SELECT * FROM team_members WHERE team_id = ? AND user_id = ?", 
            [$teamId, $userId]
        ));
        
        if (!$member) {
            throw new Exception("Mitglied nicht im Team gefunden");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Discord-Rollen entfernen
            if ($team['discord_role_id']) {
                $this->discord->removeRoleFromUser(
                    $userId, 
                    $team['discord_role_id'], 
                    $reason ?: "Aus Team entfernt"
                );
            }
            
            // Alle Team-Rollen des Mitglieds entfernen
            $teamRoles = $this->db->fetchAll($this->db->query(
                "SELECT tr.discord_role_id 
                 FROM team_member_roles tmr 
                 JOIN team_roles tr ON tmr.team_role_id = tr.id 
                 WHERE tmr.team_member_id = ? AND tr.discord_role_id IS NOT NULL",
                [$member['id']]
            ));
            
            foreach ($teamRoles as $role) {
                $this->discord->removeRoleFromUser($userId, $role['discord_role_id'], $reason);
            }
            
            // Aus Datenbank entfernen
            $this->db->query("DELETE FROM team_member_roles WHERE team_member_id = ?", [$member['id']]);
            $this->db->query("DELETE FROM team_members WHERE id = ?", [$member['id']]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Holt Team-Rolle basierend auf Typ
     */
    private function getTeamRoleByType($teamId, $roleType) {
        $roleMap = [
            'leader' => 'Teamleiter',
            'vice_captain' => 'Vize-Captain',
            'player' => 'Spieler',
            'trial' => 'Trial Spieler',
            'substitute' => 'Ersatzspieler'
        ];
        
        $roleName = $roleMap[$roleType] ?? 'Spieler';
        
        return $this->db->fetchOne($this->db->query(
            "SELECT * FROM team_roles WHERE team_id = ? AND name = ?",
            [$teamId, $roleName]
        ));
    }
    
    /**
     * Generiert eine Teamfarbe basierend auf dem Namen
     */
    private function generateTeamColor($teamName) {
        $hash = md5($teamName);
        $color = hexdec(substr($hash, 0, 6));
        
        // Sicherstellen dass Farbe hell genug ist (nicht zu dunkel)
        if ($color < 3355443) { // #333333
            $color += 6710886; // Aufhellen
        }
        
        return $color;
    }
    
    /**
     * Erstellt eine neue Team-Rolle mit Discord-Integration
     */
    public function createTeamRole($teamId, $roleData, $createdBy) {
        // Prüfen ob Benutzer berechtigt ist
        if (!$this->hasTeamPermission($createdBy, $teamId, 'can_assign_roles')) {
            throw new Exception("Keine Berechtigung zum Erstellen von Team-Rollen");
        }
        
        $team = $this->db->fetchOne($this->db->query(
            "SELECT * FROM teams WHERE id = ?", 
            [$teamId]
        ));
        
        if (!$team) {
            throw new Exception("Team nicht gefunden");
        }
        
        $this->db->beginTransaction();
        
        try {
            // Discord-Rolle erstellen falls gewünscht
            $discordRoleId = null;
            if ($roleData['create_discord_role'] ?? false) {
                $discordRole = $this->discord->createDiscordRole(
                    $team['name'] . " - " . $roleData['name'],
                    hexdec(ltrim($roleData['color'], '#')),
                    0,
                    true,
                    false
                );
                
                if ($discordRole) {
                    $discordRoleId = $discordRole['id'];
                }
            }
            
            // Team-Rolle in Datenbank erstellen
            $teamRoleId = $this->db->insert('team_roles', [
                'team_id' => $teamId,
                'name' => $roleData['name'],
                'discord_role_id' => $discordRoleId,
                'color' => $roleData['color'],
                'hierarchy_level' => $roleData['hierarchy_level'] ?? 50,
                'can_manage_members' => $roleData['can_manage_members'] ?? 0,
                'can_assign_roles' => $roleData['can_assign_roles'] ?? 0,
                'can_kick_members' => $roleData['can_kick_members'] ?? 0,
                'permissions' => json_encode($roleData['permissions'] ?? []),
                'created_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->db->commit();
            
            return [
                'success' => true,
                'team_role_id' => $teamRoleId,
                'discord_role_id' => $discordRoleId
            ];
            
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
    
    /**
     * Synchronisiert alle Team-Mitglieder mit Discord
     */
    public function syncTeamWithDiscord($teamId) {
        $team = $this->db->fetchOne($this->db->query(
            "SELECT * FROM teams WHERE id = ?", 
            [$teamId]
        ));
        
        if (!$team || !$team['discord_role_id']) {
            throw new Exception("Team oder Discord-Rolle nicht gefunden");
        }
        
        $members = $this->getTeamMembersWithRoles($teamId);
        $syncedCount = 0;
        $errors = [];
        
        foreach ($members as $member) {
            try {
                // Haupt-Team-Rolle zuweisen
                $this->discord->assignRoleToUser(
                    $member['user_id'], 
                    $team['discord_role_id'], 
                    "Team-Synchronisation"
                );
                
                // Spezifische Team-Rollen zuweisen
                $memberRoles = $this->db->fetchAll($this->db->query(
                    "SELECT tr.discord_role_id 
                     FROM team_member_roles tmr 
                     JOIN team_roles tr ON tmr.team_role_id = tr.id 
                     WHERE tmr.team_member_id = ? AND tr.discord_role_id IS NOT NULL",
                    [$member['id']]
                ));
                
                foreach ($memberRoles as $role) {
                    $this->discord->assignRoleToUser(
                        $member['user_id'], 
                        $role['discord_role_id'], 
                        "Team-Rollen-Synchronisation"
                    );
                }
                
                $syncedCount++;
                
            } catch (Exception $e) {
                $errors[] = "Fehler bei {$member['username']}: " . $e->getMessage();
            }
        }
        
        return [
            'synced_members' => $syncedCount,
            'total_members' => count($members),
            'errors' => $errors
        ];
    }
}
?>