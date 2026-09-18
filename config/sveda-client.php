<?php

return [
    'base_url' => env('SVEDA_CLIENT_BASE_URL', ''),
    'host_api_key' => env('SVEDA_CLIENT_HOST_API_KEY', ''),
    'timeout' => (int) env('SVEDA_CLIENT_TIMEOUT', 30),
    'connect_timeout' => (int) env('SVEDA_CLIENT_CONNECT_TIMEOUT', 5),

    'mcp' => [
        'path' => env('SVEDA_CLIENT_MCP_PATH', '/mcp/sveda'),
        'url' => env('SVEDA_CLIENT_MCP_URL', ''),
        'server_name' => env('SVEDA_CLIENT_MCP_SERVER_NAME', 'Host Application'),
        'server_version' => env('SVEDA_CLIENT_MCP_SERVER_VERSION', '0.1.0'),
        'instructions' => env('SVEDA_CLIENT_MCP_INSTRUCTIONS', ''),
        'token_name' => env('SVEDA_CLIENT_MCP_TOKEN_NAME', 'sveda-mcp'),
        'ability' => env('SVEDA_CLIENT_MCP_ABILITY', 'sveda:mcp'),
        'token_ttl_seconds' => (int) env('SVEDA_CLIENT_MCP_TOKEN_TTL', 3600),
        'throttle' => env('SVEDA_CLIENT_MCP_THROTTLE', '120,1'),
    ],

    'session' => [
        'enabled' => env('SVEDA_CLIENT_SESSION_ENABLED', true),
        'path' => env('SVEDA_CLIENT_SESSION_PATH', '/sveda/session'),
        'name' => env('SVEDA_CLIENT_SESSION_NAME', 'sveda.session'),
        'middleware' => ['web', 'auth'],
        'visitor_prefix' => env('SVEDA_CLIENT_VISITOR_PREFIX', 'host'),
    ],
];
