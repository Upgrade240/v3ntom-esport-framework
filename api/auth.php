<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';

$config = include '../config.php';
$db = new Database($config);

// Handle Discord OAuth2 flow
if (isset($_GET['code'])) {
    // Exchange code for access token
    $tokenData = [
        'client_id' => $config['discord']['client_id'],
        'client_secret' => $config['discord']['client_secret'],
        'grant_type' => 'authorization_code',
        'code' => $_GET['code'],
        'redirect_uri' => $config['discord']['redirect_uri']
    ];
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://discord.com/api/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($tokenData),
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);
    
    if ($httpCode !== 200) {
        die('Discord OAuth2 Error: ' . $response);
    }
    
    $tokenInfo = json_decode($response, true);
    $accessToken = $tokenInfo['access_token'];
    
    // Get user information
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => 'https://discord.com/api/users/@me',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
    ]);
    
    $userResponse = curl_exec($curl);
    curl_close($curl);
    
    $userInfo = json_decode($userResponse, true);
    
    if (!$userInfo || !isset($userInfo['id'])) {
        die('Failed to get user information from Discord');
    }
    
    // Check if user exists in database
    $user = $db->getUserByDiscordId($userInfo['id']);
    
    if ($user) {
        // Update existing user
        $db->update('users', [
            'username' => $userInfo['username'],
            'discriminator' => $userInfo['discriminator'] ?? null,
            'avatar' => $userInfo['avatar'],
            'email' => $userInfo['email'] ?? null,
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);
        
        $_SESSION['user_id'] = $user['id'];
    } else {
        // Create new user
        $userId = $db->insert('users', [
            'discord_id' => $userInfo['id'],
            'username' => $userInfo['username'],
            'discriminator' => $userInfo['discriminator'] ?? null,
            'avatar' => $userInfo['avatar'],
            'email' => $userInfo['email'] ?? null,
            'role_id' => 7, // Default to trial role
            'status' => 'trial',
            'last_login' => date('Y-m-d H:i:s')
        ]);
        
        $_SESSION['user_id'] = $userId;
    }
    
    // Redirect to main panel
    header('Location: ../index.php');
    exit;
    
} elseif (isset($_GET['logout'])) {
    // Logout
    session_destroy();
    header('Location: ../index.php');
    exit;
    
} else {
    // Redirect to Discord OAuth2
    $params = [
        'client_id' => $config['discord']['client_id'],
        'redirect_uri' => $config['discord']['redirect_uri'],
        'response_type' => 'code',
        'scope' => 'identify email guilds'
    ];
    
    $authUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
    header('Location: ' . $authUrl);
    exit;
}
?>
