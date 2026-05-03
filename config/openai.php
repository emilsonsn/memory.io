<?php

return [
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'project' => env('OPENAI_PROJECT'),
    'base_uri' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
    'request_timeout' => (int) env('OPENAI_REQUEST_TIMEOUT', 30),

    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4.1-mini'),
    'max_context_chars' => (int) env('OPENAI_MAX_CONTEXT_CHARS', 6000),
    'max_output_tokens' => (int) env('OPENAI_MAX_OUTPUT_TOKENS', 350),
];
