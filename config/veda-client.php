<?php

return [
    'base_url' => env('VEDA_CLIENT_BASE_URL', ''),
    'host_api_key' => env('VEDA_CLIENT_HOST_API_KEY', ''),
    'timeout' => (int) env('VEDA_CLIENT_TIMEOUT', 30),
    'connect_timeout' => (int) env('VEDA_CLIENT_CONNECT_TIMEOUT', 5),

    'mcp' => [
        'path' => env('VEDA_CLIENT_MCP_PATH', '/mcp/veda'),
        'url' => env('VEDA_CLIENT_MCP_URL', ''),
        'server_name' => env('VEDA_CLIENT_MCP_SERVER_NAME', 'Host Application'),
        'server_version' => env('VEDA_CLIENT_MCP_SERVER_VERSION', '0.1.0'),
        'token_name' => env('VEDA_CLIENT_MCP_TOKEN_NAME', 'veda-mcp'),
        'ability' => env('VEDA_CLIENT_MCP_ABILITY', 'veda:mcp'),
        'token_ttl_seconds' => (int) env('VEDA_CLIENT_MCP_TOKEN_TTL', 3600),
        'throttle' => env('VEDA_CLIENT_MCP_THROTTLE', '120,1'),
    ],

    'session' => [
        'visitor_prefix' => env('VEDA_CLIENT_VISITOR_PREFIX', 'host'),
    ],
];
