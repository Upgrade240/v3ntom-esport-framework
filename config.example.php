<?php
return [
    'installed' => false,
    'base_url' => 'http://localhost',
    
    'database' => [
        'host' => 'localhost',
        'name' => 'v3ntom_esport',
        'user' => 'root',
        'pass' => 'password',
        'charset' => 'utf8mb4'
    ],
    
    'discord' => [
        'token' => 'YOUR_BOT_TOKEN',
        'client_id' => 'YOUR_CLIENT_ID',
        'client_secret' => 'YOUR_CLIENT_SECRET',
        'guild_id' => 'YOUR_SERVER_ID',
        'redirect_uri' => 'http://localhost/api/auth.php'
    ],
    
    'teamspeak' => [
        'host' => '',
        'port' => '10011',
        'user' => '',
        'pass' => '',
        'server_id' => '1'
    ]
];
?>
