<?php

class Database {
    private $connection;
    private $config;
    
    public function __construct($config) {
        $this->config = $config['database'];
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->connection = new mysqli(
                $this->config['host'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['name']
            );
            
            if ($this->connection->connect_error) {
                throw new Exception("Database connection failed: " . $this->connection->connect_error);
            }
            
            $this->connection->set_charset($this->config['charset']);
            
        } catch (Exception $e) {
            error_log("Database Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    public function query($sql, $params = []) {
        try {
            if (empty($params)) {
                $result = $this->connection->query($sql);
                if ($result === false) {
                    throw new Exception("Query failed: " . $this->connection->error);
                }
                return $result;
            }
            
            $stmt = $this->connection->prepare($sql);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->connection->error);
            }
            
            if (!empty($params)) {
                $types = '';
                $values = [];
                
                foreach ($params as $param) {
                    if (is_int($param)) {
                        $types .= 'i';
                    } elseif (is_double($param)) {
                        $types .= 'd';
                    } else {
                        $types .= 's';
                    }
                    $values[] = $param;
                }
                
                $stmt->bind_param($types, ...$values);
            }
            
            $stmt->execute();
            
            if ($stmt->error) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database Query Error: " . $e->getMessage());
            error_log("SQL: " . $sql);
            error_log("Params: " . print_r($params, true));
            throw $e;
        }
    }
    
    public function insert($table, $data) {
        $columns = array_keys($data);
        $placeholders = array_fill(0, count($data), '?');
        
        $sql = "INSERT INTO `{$table}` (`" . implode('`, `', $columns) . "`) VALUES (" . implode(', ', $placeholders) . ")";
        
        $this->query($sql, array_values($data));
        return $this->connection->insert_id;
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "`{$column}` = ?";
        }
        
        $sql = "UPDATE `{$table}` SET " . implode(', ', $setParts) . " WHERE {$where}";
        
        $params = array_merge(array_values($data), $whereParams);
        $this->query($sql, $params);
        
        return $this->connection->affected_rows;
    }
    
    public function delete($table, $where, $params = []) {
        $sql = "DELETE FROM `{$table}` WHERE {$where}";
        $this->query($sql, $params);
        return $this->connection->affected_rows;
    }
    
    public function select($table, $columns = '*', $where = '', $params = [], $orderBy = '', $limit = '') {
        $sql = "SELECT {$columns} FROM `{$table}`";
        
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        if (!empty($orderBy)) {
            $sql .= " ORDER BY {$orderBy}";
        }
        
        if (!empty($limit)) {
            $sql .= " LIMIT {$limit}";
        }
        
        return $this->query($sql, $params);
    }
    
    public function fetchAll($result) {
        if ($result instanceof mysqli_result) {
            return $result->fetch_all(MYSQLI_ASSOC);
        }
        return [];
    }
    
    public function fetchOne($result) {
        if ($result instanceof mysqli_result) {
            return $result->fetch_assoc();
        }
        return null;
    }
    
    public function escape($string) {
        return $this->connection->real_escape_string($string);
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function beginTransaction() {
        return $this->connection->begin_transaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollback() {
        return $this->connection->rollback();
    }
    
    public function close() {
        if ($this->connection) {
            $this->connection->close();
        }
    }
    
    // Utility methods for common operations
    public function getUserById($id) {
        $result = $this->select('users', '*', 'id = ?', [$id]);
        return $this->fetchOne($result);
    }
    
    public function getUserByDiscordId($discordId) {
        $result = $this->select('users', '*', 'discord_id = ?', [$discordId]);
        return $this->fetchOne($result);
    }
    
    public function getTeamById($id) {
        $result = $this->select('teams', '*', 'id = ?', [$id]);
        return $this->fetchOne($result);
    }
    
    public function getRoleById($id) {
        $result = $this->select('roles', '*', 'id = ?', [$id]);
        return $this->fetchOne($result);
    }
    
    public function getTeamMembers($teamId) {
        $sql = "SELECT tm.*, u.username, u.avatar, u.discord_id 
                FROM team_members tm 
                JOIN users u ON tm.user_id = u.id 
                WHERE tm.team_id = ? 
                ORDER BY tm.role DESC, tm.joined_at ASC";
        
        $result = $this->query($sql, [$teamId]);
        return $this->fetchAll($result);
    }
    
    public function getUserTeams($userId) {
        $sql = "SELECT t.*, tm.role as member_role 
                FROM teams t 
                JOIN team_members tm ON t.id = tm.team_id 
                WHERE tm.user_id = ?";
        
        $result = $this->query($sql, [$userId]);
        return $this->fetchAll($result);
    }
    
    public function getPendingApplications($teamId = null) {
        $where = "status = 'pending'";
        $params = [];
        
        if ($teamId) {
            $where .= " AND team_id = ?";
            $params[] = $teamId;
        }
        
        $sql = "SELECT ta.*, t.name as team_name, u.username, u.avatar 
                FROM team_applications ta 
                JOIN teams t ON ta.team_id = t.id 
                JOIN users u ON ta.user_id = u.id 
                WHERE {$where} 
                ORDER BY ta.created_at DESC";
        
        $result = $this->query($sql, $params);
        return $this->fetchAll($result);
    }
    
    public function getRecentActivity($limit = 50) {
        $sql = "SELECT ml.*, 
                       u1.username as target_username,
                       u2.username as moderator_username
                FROM moderation_logs ml 
                JOIN users u1 ON ml.target_user_id = u1.id 
                JOIN users u2 ON ml.moderator_id = u2.id 
                ORDER BY ml.created_at DESC 
                LIMIT ?";
        
        $result = $this->query($sql, [$limit]);
        return $this->fetchAll($result);
    }
    
    public function getUpcomingEvents($limit = 10) {
        $sql = "SELECT e.*, t.name as team_name, u.username as created_by_username
                FROM events e 
                LEFT JOIN teams t ON e.team_id = t.id 
                JOIN users u ON e.created_by = u.id 
                WHERE e.start_date >= NOW() 
                ORDER BY e.start_date ASC 
                LIMIT ?";
        
        $result = $this->query($sql, [$limit]);
        return $this->fetchAll($result);
    }
    
    public function getStatistics() {
        $stats = [];
        
        // Total users
        $result = $this->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
        $stats['total_users'] = $this->fetchOne($result)['count'];
        
        // New users this week
        $result = $this->query("SELECT COUNT(*) as count FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
        $stats['new_users_week'] = $this->fetchOne($result)['count'];
        
        // Total teams
        $result = $this->query("SELECT COUNT(*) as count FROM teams");
        $stats['total_teams'] = $this->fetchOne($result)['count'];
        
        // Open applications
        $result = $this->query("SELECT COUNT(*) as count FROM team_applications WHERE status = 'pending'");
        $stats['pending_applications'] = $this->fetchOne($result)['count'];
        
        // Open tickets
        $result = $this->query("SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'in_progress')");
        $stats['open_tickets'] = $this->fetchOne($result)['count'];
        
        // Upcoming events
        $result = $this->query("SELECT COUNT(*) as count FROM events WHERE start_date >= NOW() AND start_date <= DATE_ADD(NOW(), INTERVAL 7 DAY)");
        $stats['upcoming_events'] = $this->fetchOne($result)['count'];
        
        return $stats;
    }
}
?>
