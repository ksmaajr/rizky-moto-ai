<?php

return [
    // Client-facing credential. Keep this on your server, never in browser JavaScript.
    'key' => env('RIZKY_IMAGE_API_KEY'),

    // Server-side provider credential and OpenAI-compatible gateway endpoint.
    'provider_key' => env('AI_GATEWAY_API_KEY'),
    'base_url' => env('AI_GATEWAY_BASE_URL', 'https://ai-gateway.vercel.sh/v1'),
    'model' => env('AI_GATEWAY_IMAGE_MODEL', 'openai/gpt-image-1'),
    'timeout' => (int) env('AI_GATEWAY_TIMEOUT', 180),
];
