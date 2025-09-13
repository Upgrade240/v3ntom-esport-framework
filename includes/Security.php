<?php

class Security {
    private static $csrfTokens = [];
    
    public static function generateCSRFToken($form = 'default') {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_tokens'][$form] = $token;
        self::$csrfTokens[$form] = $token;
        
        return $token;
    }
    
    public static function validateCSRFToken($token, $form = 'default') {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['csrf_tokens'][$form])) {
            return false;
        }
        
        $valid = hash_equals($_SESSION['csrf_tokens'][$form], $token);
        
        // Remove token after use (one-time use)
        unset($_SESSION['csrf_tokens'][$form]);
        
        return $valid;
    }
    
    public static function sanitizeInput($input, $type = 'string') {
        switch ($type) {
            case 'string':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
                
            case 'email':
                return filter_var($input, FILTER_SANITIZE_EMAIL);
                
            case 'int':
                return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'url':
                return filter_var($input, FILTER_SANITIZE_URL);
                
            case 'html':
                // Allow basic HTML tags but remove dangerous ones
                return strip_tags($input, '<p><br><strong><em><u><a><ul><ol><li>');
                
            default:
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    public static function validateInput($input, $type, $required = true, $options = []) {
        if ($required && empty($input)) {
            throw new InvalidArgumentException("Field is required");
        }
        
        if (empty($input) && !$required) {
            return true;
        }
        
        switch ($type) {
            case 'email':
                if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
                    throw new InvalidArgumentException("Invalid email format");
                }
                break;
                
            case 'url':
                if (!filter_var($input, FILTER_VALIDATE_URL)) {
                    throw new InvalidArgumentException("Invalid URL format");
                }
                break;
                
            case 'int':
                if (!filter_var($input, FILTER_VALIDATE_INT)) {
                    throw new InvalidArgumentException("Invalid integer");
                }
                if (isset($options['min']) && $input < $options['min']) {
                    throw new InvalidArgumentException("Value too small (min: {$options['min']})");
                }
                if (isset($options['max']) && $input > $options['max']) {
                    throw new InvalidArgumentException("Value too large (max: {$options['max']})");
                }
                break;
                
            case 'string':
                if (isset($options['min_length']) && strlen($input) < $options['min_length']) {
                    throw new InvalidArgumentException("String too short (min: {$options['min_length']})");
                }
                if (isset($options['max_length']) && strlen($input) > $options['max_length']) {
                    throw new InvalidArgumentException("String too long (max: {$options['max_length']})");
                }
                if (isset($options['pattern']) && !preg_match($options['pattern'], $input)) {
                    throw new InvalidArgumentException("String format invalid");
                }
                break;
                
            case 'discord_id':
                if (!preg_match('/^\d{17,19}$/', $input)) {
                    throw new InvalidArgumentException("Invalid Discord ID format");
                }
                break;
                
            case 'username':
                if (!preg_match('/^[a-zA-Z0-9_\-]{3,32}$/', $input)) {
                    throw new InvalidArgumentException("Username must be 3-32 characters, alphanumeric, underscore or dash only");
                }
                break;
        }
        
        return true;
    }
    
    public static function rateLimitCheck($key, $limit = 60, $window = 3600) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        $now = time();
        $windowStart = $now - $window;
        
        // Initialize rate limit data if not exists
        if (!isset($_SESSION['rate_limits'][$key])) {
            $_SESSION['rate_limits'][$key] = [];
        }
        
        // Clean old entries
        $_SESSION['rate_limits'][$key] = array_filter(
            $_SESSION['rate_limits'][$key],
            function($timestamp) use ($windowStart) {
                return $timestamp > $windowStart;
            }
        );
        
        // Check if limit exceeded
        if (count($_SESSION['rate_limits'][$key]) >= $limit) {
            return false;
        }
        
        // Add current request
        $_SESSION['rate_limits'][$key][] = $now;
        
        return true;
    }
    
    public static function checkAuth($requiredPermission = null) {
        if (!isset($_SESSION)) {
            session_start();
        }
        
        if (!isset($_SESSION['user_id'])) {
            http_response_code(401);
            if (self::isApiRequest()) {
                echo json_encode(['error' => 'Authentication required']);
            } else {
                header('Location: /api/auth.php');
            }
            exit;
        }
        
        // Check session timeout
        $timeout = 7200; // 2 hours
        if (isset($_SESSION['last_activity']) && 
            (time() - $_SESSION['last_activity']) > $timeout) {
            session_destroy();
            self::checkAuth();
        }
        
        $_SESSION['last_activity'] = time();
        
        // Check specific permission if required
        if ($requiredPermission) {
            $config = include dirname(__DIR__) . '/config.php';
            require_once dirname(__DIR__) . '/includes/Database.php';
            require_once dirname(__DIR__) . '/includes/PermissionManager.php';
            
            $db = new Database($config);
            $permissions = new PermissionManager($db);
            
            if (!$permissions->hasPermission($_SESSION['user_id'], $requiredPermission)) {
                http_response_code(403);
                if (self::isApiRequest()) {
                    echo json_encode(['error' => 'Permission denied']);
                } else {
                    header('Location: /403.php');
                }
                exit;
            }
        }
        
        return $_SESSION['user_id'];
    }
    
    private static function isApiRequest() {
        return strpos($_SERVER['REQUEST_URI'], '/api/') !== false ||
               (isset($_SERVER['HTTP_ACCEPT']) && 
                strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }
    
    public static function logSecurityEvent($event, $details = [], $severity = 'INFO') {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'severity' => $severity,
            'user_id' => $_SESSION['user_id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logFile = dirname(__DIR__) . '/logs/security.log';
        error_log("SECURITY_EVENT: " . json_encode($logEntry) . "\n", 3, $logFile);
        
        // Alert on critical events
        if ($severity === 'CRITICAL') {
            self::sendSecurityAlert($logEntry);
        }
    }
    
    private static function sendSecurityAlert($logEntry) {
        // Implementation for critical security alerts
        // Could send to Discord webhook, email admin, etc.
        error_log("CRITICAL SECURITY EVENT: " . json_encode($logEntry));
    }
    
    public static function generateSecurePassword($length = 16) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
        $password = '';
        
        for ($i = 0; $i < $length; $i++) {
            $password .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $password;
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID, [
            'memory_cost' => 65536, // 64 MB
            'time_cost' => 4,       // 4 iterations
            'threads' => 3          // 3 threads
        ]);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function createSecureSession() {
        if (session_status() === PHP_SESSION_NONE) {
            // Secure session configuration
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');
            
            session_start();
            
            // Regenerate session ID to prevent session fixation
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id(true);
                $_SESSION['initiated'] = true;
            }
        }
    }
    
    public static function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = [];
            
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }
            
            session_destroy();
        }
    }
}
?>
