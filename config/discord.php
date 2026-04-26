<?php

return [
    'authorization_url' => env('DISCORD_AUTHORIZATION_URL', 'https://discord.com/oauth2/authorize'),
    'token_url' => env('DISCORD_TOKEN_URL', 'https://discord.com/api/oauth2/token'),
    'api_base_url' => env('DISCORD_API_BASE_URL', 'https://discord.com/api'),
    'cdn_base_url' => env('DISCORD_CDN_BASE_URL', 'https://cdn.discordapp.com'),
    'bot_token' => env('DISCORD_BOT_TOKEN'),
    'oauth_scopes' => [
        'identify',
        'email',
        'guilds.members.read',
    ],
];
