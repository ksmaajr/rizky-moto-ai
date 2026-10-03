<?php

return [
    'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'default_model' => env('OPENAI_IMAGE_MODEL', 'gpt-image-2'),
    'timeout' => (int) env('OPENAI_TIMEOUT', 60),
];
