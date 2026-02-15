<?php
session_start();
require_once '../config.php';
require_once '../includes/Database.php';

$config = include '../config.php';
$db = new Database($config);

function discordApiRequest(string $url, string $accessToken): array {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $accessToken]
    ]);

    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    return [$httpCode, json_decode($response, true)];
}

if (isset($_GET['code'])) {
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

    [$userHttpCode, $userInfo] = discordApiRequest('https://discord.com/api/users/@me', $accessToken);
    if ($userHttpCode !== 200 || !$userInfo || !isset($userInfo['id'])) {
        die('Failed to get user information from Discord');
    }

    [$guildHttpCode, $guilds] = discordApiRequest('https://discord.com/api/users/@me/guilds', $accessToken);
    if ($guildHttpCode !== 200 || !is_array($guilds)) {
        die('Failed to validate server membership.');
    }

    $requiredGuildId = $config['discord']['guild_id'] ?? null;
    $isMember = false;
    foreach ($guilds as $guild) {
        if (($guild['id'] ?? null) === $requiredGuildId) {
            $isMember = true;
            break;
        }
    }

    if (!$isMember) {
        session_destroy();
        http_response_code(403);
        echo 'Login verweigert: Du musst Mitglied auf dem V3NTOM Discord Server sein.';
        exit;
    }

    $user = $db->getUserByDiscordId($userInfo['id']);

    if ($user) {
        $db->update('users', [
            'username' => $userInfo['username'],
            'discriminator' => $userInfo['discriminator'] ?? null,
            'avatar' => $userInfo['avatar'],
            'email' => $userInfo['email'] ?? null,
            'status' => 'active',
            'last_login' => date('Y-m-d H:i:s')
        ], 'id = ?', [$user['id']]);

        $_SESSION['user_id'] = $user['id'];
    } else {
        $userId = $db->insert('users', [
            'discord_id' => $userInfo['id'],
            'username' => $userInfo['username'],
            'discriminator' => $userInfo['discriminator'] ?? null,
            'avatar' => $userInfo['avatar'],
            'email' => $userInfo['email'] ?? null,
            'role_id' => 7,
            'status' => 'active',
            'last_login' => date('Y-m-d H:i:s')
        ]);

        $_SESSION['user_id'] = $userId;
    }

    $_SESSION['discord_id'] = $userInfo['id'];
    $_SESSION['username'] = $userInfo['username'];

    header('Location: ../dashboard.php');
    exit;
} elseif (isset($_GET['logout'])) {
    session_destroy();
    header('Location: ../index.php');
    exit;
}

$params = [
    'client_id' => $config['discord']['client_id'],
    'redirect_uri' => $config['discord']['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'identify email guilds'
];

$authUrl = 'https://discord.com/api/oauth2/authorize?' . http_build_query($params);
header('Location: ' . $authUrl);
exit;
