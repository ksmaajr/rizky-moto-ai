<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'agent_ai' => [
        'python_binary' => env('AGENT_AI_PYTHON_BINARY', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'),
        'module' => env('AGENT_AI_MODULE', 'gpt_image25_agent'),
        'timeout' => (int) env('AGENT_AI_TIMEOUT', 300),
        // Maximum credential-pool attempts for a single generation (provider clamps to 1..5).
        'credential_retry_attempts' => (int) env('AGENT_AI_CREDENTIAL_RETRY_ATTEMPTS', 3),
        'queue' => env('AGENT_AI_QUEUE', 'agentkit'),
        'worker_count' => (int) env('AGENT_AI_WORKER_COUNT', 3),
        'worker_driver' => env('AGENT_AI_WORKER_DRIVER', PHP_OS_FAMILY === 'Windows' ? 'local' : 'supervisor'),
        'worker_supervisor_program' => env('AGENT_AI_WORKER_SUPERVISOR_PROGRAM', 'rizky-moto-ai-agent'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
