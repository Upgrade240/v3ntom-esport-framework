-- sql/discord_integration.sql
-- Erweiterte Tabellen für Discord-Integration

-- Discord-Rollen
CREATE TABLE discord_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    discord_role_id VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    color INT DEFAULT 0,
    position INT DEFAULT 0,
    permissions BIGINT DEFAULT 0,
    mentionable BOOLEAN DEFAULT FALSE,
    hoist BOOLEAN DEFAULT FALSE,
    managed BOOLEAN DEFAULT FALSE,
    team_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_discord_role_id (discord_role_id),
    INDEX idx_team_id (team_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE SET NULL
);

-- Benutzer Discord-Rollen Zuordnung
CREATE TABLE user_discord_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    discord_role_id VARCHAR(20) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NULL,
    assigned_reason VARCHAR(255) NULL,
    
    UNIQUE KEY unique_user_role (user_id, discord_role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_discord_role_id (discord_role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Discord Ankündigungen
CREATE TABLE discord_announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    discord_message_id VARCHAR(20) NOT NULL UNIQUE,
    channel_id VARCHAR(20) NOT NULL,
    author_id VARCHAR(20) NOT NULL,
    content TEXT,
    embed_data JSON NULL,
    attachment_data JSON NULL,
    announcement_type ENUM('general', 'team', 'event', 'update') DEFAULT 'general',
    related_id INT NULL, -- ID des zugehörigen Teams/Events
    discord_timestamp TIMESTAMP NOT NULL,
    imported_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_pinned BOOLEAN DEFAULT FALSE,
    
    INDEX idx_channel_id (channel_id),
    INDEX idx_author_id (author_id),
    INDEX idx_type_related (announcement_type, related_id),
    INDEX idx_discord_timestamp (discord_timestamp)
);

-- Erweiterte Teams-Tabelle (Update der bestehenden)
ALTER TABLE teams ADD COLUMN discord_role_id VARCHAR(20) NULL AFTER name;
ALTER TABLE teams ADD COLUMN auto_role_assignment BOOLEAN DEFAULT TRUE;
ALTER TABLE teams ADD COLUMN discord_announcement_sent BOOLEAN DEFAULT FALSE;
ALTER TABLE teams ADD INDEX idx_discord_role_id (discord_role_id);

-- Team-Rollen (Hierarchie innerhalb Teams)
CREATE TABLE team_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_id INT NOT NULL,
    name VARCHAR(50) NOT NULL,
    discord_role_id VARCHAR(20) NULL,
    color VARCHAR(7) DEFAULT '#99AAB5',
    permissions JSON NULL, -- Team-spezifische Berechtigungen
    hierarchy_level INT DEFAULT 0, -- 0 = niedrigste, höhere Zahl = mehr Rechte
    can_manage_members BOOLEAN DEFAULT FALSE,
    can_assign_roles BOOLEAN DEFAULT FALSE,
    can_kick_members BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_team_role_name (team_id, name),
    INDEX idx_team_id (team_id),
    INDEX idx_discord_role_id (discord_role_id),
    FOREIGN KEY (team_id) REFERENCES teams(id) ON DELETE CASCADE
);

-- Team-Mitglieder mit Rollen
CREATE TABLE team_member_roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_member_id INT NOT NULL,
    team_role_id INT NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    assigned_by INT NULL,
    
    UNIQUE KEY unique_member_role (team_member_id, team_role_id),
    INDEX idx_team_member_id (team_member_id),
    INDEX idx_team_role_id (team_role_id),
    FOREIGN KEY (team_member_id) REFERENCES team_members(id) ON DELETE CASCADE,
    FOREIGN KEY (team_role_id) REFERENCES team_roles(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Discord Permissions für Framework-Rollen
CREATE TABLE role_discord_permissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    role_id INT NOT NULL,
    can_assign_discord_roles JSON NULL, -- Array von Discord-Rollen-IDs die zugewiesen werden können
    can_manage_teams BOOLEAN DEFAULT FALSE,
    can_create_team_roles BOOLEAN DEFAULT FALSE,
    max_hierarchy_level INT DEFAULT 0, -- Maximales Level das verwaltet werden kann
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_role_permissions (role_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
);

-- Discord Sync Log
CREATE TABLE discord_sync_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sync_type ENUM('roles', 'members', 'announcements', 'team_creation') NOT NULL,
    status ENUM('running', 'completed', 'failed') NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    items_processed INT DEFAULT 0,
    items_total INT DEFAULT 0,
    error_message TEXT NULL,
    details JSON NULL,
    
    INDEX idx_sync_type (sync_type),
    INDEX idx_status (status),
    INDEX idx_started_at (started_at)
);

-- Standard Team-Rollen einfügen
INSERT INTO team_roles (team_id, name, color, hierarchy_level, can_manage_members, can_assign_roles, can_kick_members) VALUES
-- Diese werden nach Team-Erstellung automatisch eingefügt
(0, 'Teamleiter', '#E74C3C', 100, TRUE, TRUE, TRUE),
(0, 'Vize-Captain', '#F39C12', 80, TRUE, TRUE, FALSE),
(0, 'Spieler', '#2ECC71', 50, FALSE, FALSE, FALSE),
(0, 'Trial Spieler', '#3498DB', 20, FALSE, FALSE, FALSE),
(0, 'Ersatzspieler', '#95A5A6', 10, FALSE, FALSE, FALSE);

-- Standard Discord-Berechtigungen für Framework-Rollen
INSERT INTO role_discord_permissions (role_id, can_assign_discord_roles, can_manage_teams, can_create_team_roles, max_hierarchy_level) VALUES
-- Admin kann alles
(1, '["*"]', TRUE, TRUE, 999),
-- Moderator kann Teams verwalten aber nicht alles
(2, '[]', TRUE, TRUE, 80),
-- Member kann nur eigene Team-Rollen verwalten
(3, '[]', FALSE, FALSE, 50);

-- Erweiterte Users-Tabelle (Update)
ALTER TABLE users ADD COLUMN discriminator VARCHAR(4) DEFAULT '0' AFTER username;
ALTER TABLE users ADD COLUMN nick VARCHAR(32) NULL AFTER discriminator;
ALTER TABLE users ADD COLUMN bot BOOLEAN DEFAULT FALSE AFTER nick;
ALTER TABLE users ADD COLUMN joined_discord_at TIMESTAMP NULL AFTER bot;