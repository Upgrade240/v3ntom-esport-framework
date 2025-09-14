<?php
// includes/AnnouncementManager.php

class AnnouncementManager {
    private $db;
    private $discord;
    private $config;
    
    public function __construct($database, $discordIntegration, $config) {
        $this->db = $database;
        $this->discord = $discordIntegration;
        $this->config = $config;
    }
    
    /**
     * Importiert Ankündigungen aus Discord-Channel
     */
    public function importDiscordAnnouncements($channelId = null, $limit = 50) {
        if (!$channelId) {
            $channelId = $this->config['discord']['announcement_channel'];
        }
        
        if (!$channelId) {
            throw new Exception("Kein Ankündigungs-Channel konfiguriert");
        }
        
        $messages = $this->discord->getAnnouncementsFromChannel($channelId, $limit);
        $importedCount = 0;
        
        foreach ($messages as $message) {
            // Prüfen ob Nachricht bereits importiert wurde
            $existing = $this->db->fetchOne($this->db->query(
                "SELECT id FROM discord_announcements WHERE discord_message_id = ?",
                [$message['id']]
            ));
            
            if ($existing) {
                continue; // Bereits importiert
            }
            
            // Announcement-Typ ermitteln
            $type = $this->determineAnnouncementType($message['content']);
            $relatedId = $this->findRelatedEntity($message['content'], $type);
            
            $this->db->insert('discord_announcements', [
                'discord_message_id' => $message['id'],
                'channel_id' => $channelId,
                'author_id' => $message['author_id'],
                'content' => $message['content'],
                'embed_data' => !empty($message['embeds']) ? json_encode($message['embeds']) : null,
                'attachment_data' => !empty($message['attachments']) ? json_encode($message['attachments']) : null,
                'announcement_type' => $type,
                'related_id' => $relatedId,
                'discord_timestamp' => date('Y-m-d H:i:s', strtotime($message['timestamp'])),
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            
            $importedCount++;
        }
        
        return $importedCount;
    }
    
    /**
     * Ermittelt den Typ einer Ankündigung basierend auf Inhalt
     */
    private function determineAnnouncementType($content) {
        // Team-bezogene Keywords
        if (preg_match('/team.*erstellt|neues team|team.*gegründet/i', $content)) {
            return 'team';
        }
        
        // Event-bezogene Keywords
        if (preg_match('/event|turnier|match|training|scrim/i', $content)) {
            return 'event';
        }
        
        // Update-bezogene Keywords
        if (preg_match('/update|version|changelog|patch/i', $content)) {
            return 'update';
        }
        
        return 'general';
    }
    
    /**
     * Sucht nach verwandten Entitäten (Teams, Events) in der Ankündigung
     */
    private function findRelatedEntity($content, $type) {
        if ($type === 'team') {
            // Nach Team-Namen in der Ankündigung suchen
            $teams = $this->db->fetchAll($this->db->query("SELECT id, name FROM teams"));
            
            foreach ($teams as $team) {
                if (stripos($content, $team['name']) !== false) {
                    return $team['id'];
                }
            }
        }
        
        if ($type === 'event') {
            // Nach Event-Titel suchen
            $events = $this->db->fetchAll($this->db->query(
                "SELECT id, title FROM events WHERE start_date >= DATE_SUB(NOW(), INTERVAL 1 DAY)"
            ));
            
            foreach ($events as $event) {
                if (stripos($content, $event['title']) !== false) {
                    return $event['id'];
                }
            }
        }
        
        return null;
    }
    
    /**
     * Holt alle Ankündigungen für die Website-Anzeige
     */
    public function getAnnouncementsForWebsite($limit = 10, $type = null) {
        $sql = "
            SELECT 
                da.*,
                CASE 
                    WHEN da.announcement_type = 'team' THEN t.name
                    WHEN da.announcement_type = 'event' THEN e.title
                    ELSE NULL
                END as related_name,
                CASE
                    WHEN da.announcement_type = 'team' THEN CONCAT('/teams/', t.id)
                    WHEN da.announcement_type = 'event' THEN CONCAT('/events/', e.id)
                    ELSE NULL
                END as related_url
            FROM discord_announcements da
            LEFT JOIN teams t ON da.announcement_type = 'team' AND da.related_id = t.id
            LEFT JOIN events e ON da.announcement_type = 'event' AND da.related_id = e.id
        ";
        
        $params = [];
        
        if ($type) {
            $sql .= " WHERE da.announcement_type = ?";
            $params[] = $type;
        }
        
        $sql .= " ORDER BY da.discord_timestamp DESC LIMIT ?";
        $params[] = $limit;
        
        $result = $this->db->query($sql, $params);
        return $this->db->fetchAll($result);
    }
    
    /**
     * Erstellt eine neue Ankündigung und sendet sie an Discord
     */
    public function createAnnouncement($data) {
        $channelId = $this->config['discord']['announcement_channel'];
        
        if (!$channelId) {
            throw new Exception("Kein Ankündigungs-Channel konfiguriert");
        }
        
        // Embed für Ankündigung erstellen
        $embed = $this->createAnnouncementEmbed($data);
        
        // An Discord senden
        $message = $this->discord->sendMessage($channelId, $data['content'] ?? '', [$embed]);
        
        if ($message) {
            // In Datenbank speichern
            $this->db->insert('discord_announcements', [
                'discord_message_id' => $message['id'],
                'channel_id' => $channelId,
                'author_id' => $data['author_discord_id'] ?? 'system',
                'content' => $data['content'] ?? '',
                'embed_data' => json_encode([$embed]),
                'announcement_type' => $data['type'] ?? 'general',
                'related_id' => $data['related_id'] ?? null,
                'discord_timestamp' => date('Y-m-d H:i:s'),
                'imported_at' => date('Y-m-d H:i:s')
            ]);
            
            return $message;
        }
        
        return false;
    }
    
    /**
     * Erstellt Discord-Embed für Ankündigung
     */
    private function createAnnouncementEmbed($data) {
        $embed = [
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'color' => $this->getColorForType($data['type'] ?? 'general'),
            'timestamp' => date('c'),
            'footer' => [
                'text' => 'V3NTOM E-Sport Framework'
            ]
        ];
        
        // Thumbnail basierend auf Typ
        if ($data['type'] === 'team' && !empty($data['team_logo'])) {
            $embed['thumbnail'] = ['url' => $data['team_logo']];
        }
        
        // Felder hinzufügen
        if (!empty($data['fields'])) {
            $embed['fields'] = $data['fields'];
        }
        
        // Autor-Information
        if (!empty($data['author_name'])) {
            $embed['author'] = [
                'name' => $data['author_name']
            ];
            
            if (!empty($data['author_avatar'])) {
                $embed['author']['icon_url'] = $data['author_avatar'];
            }
        }
        
        return $embed;
    }
    
    /**
     * Holt Farbe basierend auf Ankündigungs-Typ
     */
    private function getColorForType($type) {
        $colors = [
            'general' => 5865242,  // Discord Blau
            'team' => 3066993,     // Grün
            'event' => 15844367,   // Gold
            'update' => 10181046   // Lila
        ];
        
        return $colors[$type] ?? $colors['general'];
    }
    
    /**
     * Synchronisiert alle Ankündigungs-Channels
     */
    public function syncAllAnnouncementChannels() {
        $channels = $this->config['discord']['sync_channels'] ?? [];
        $totalImported = 0;
        
        foreach ($channels as $channelId) {
            try {
                $imported = $this->importDiscordAnnouncements($channelId);
                $totalImported += $imported;
                
                // Log-Eintrag erstellen
                $this->db->insert('discord_sync_log', [
                    'sync_type' => 'announcements',
                    'status' => 'completed',
                    'items_processed' => $imported,
                    'completed_at' => date('Y-m-d H:i:s'),
                    'details' => json_encode(['channel_id' => $channelId])
                ]);
                
            } catch (Exception $e) {
                error_log("Fehler beim Synchronisieren von Channel {$channelId}: " . $e->getMessage());
                
                // Fehler-Log erstellen
                $this->db->insert('discord_sync_log', [
                    'sync_type' => 'announcements',
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'completed_at' => date('Y-m-d H:i:s'),
                    'details' => json_encode(['channel_id' => $channelId])
                ]);
            }
        }
        
        return $totalImported;
    }
    
    /**
     * Holt gepinnte Ankündigungen für prominente Anzeige
     */
    public function getPinnedAnnouncements() {
        $sql = "
            SELECT da.*, 
                   CASE 
                       WHEN da.announcement_type = 'team' THEN t.name
                       WHEN da.announcement_type = 'event' THEN e.title
                       ELSE NULL
                   END as related_name
            FROM discord_announcements da
            LEFT JOIN teams t ON da.announcement_type = 'team' AND da.related_id = t.id
            LEFT JOIN events e ON da.announcement_type = 'event' AND da.related_id = e.id
            WHERE da.is_pinned = 1
            ORDER BY da.discord_timestamp DESC
        ";
        
        $result = $this->db->query($sql);
        return $this->db->fetchAll($result);
    }
    
    /**
     * Markiert Ankündigung als gepinnt/ungepinnt
     */
    public function togglePinAnnouncement($announcementId, $pinned = true) {
        return $this->db->update(
            'discord_announcements',
            ['is_pinned' => $pinned ? 1 : 0],
            'id = ?',
            [$announcementId]
        );
    }
    
    /**
     * Bereinigt alte Ankündigungen
     */
    public function cleanupOldAnnouncements($daysToKeep = 30) {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$daysToKeep} days"));
        
        $deletedCount = $this->db->query(
            "DELETE FROM discord_announcements 
             WHERE discord_timestamp < ? AND is_pinned = 0",
            [$cutoffDate]
        );
        
        return $deletedCount;
    }
}
?>