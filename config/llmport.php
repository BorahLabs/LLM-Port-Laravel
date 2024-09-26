<?php

return [
    'drivers' => [
        'openai' => [
            'key' => env('OPENAI_API_KEY'),
            'default_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'organization' => env('OPENAI_ORGANIZATION'),
            'base_uri' => env('OPENAI_BASE_URI'),
        ],
        'gemini' => [
            'key' => env('GEMINI_API_KEY'),
            'default_model' => env('GEMINI_MODEL', 'gemini-1.5-flash-latest'),
        ],
        'anthropic' => [
            'key' => env('ANTHROPIC_API_KEY'),
            'default_model' => env('ANTHROPIC_MODEL', 'claude-3-5-sonnet-20240620'),
        ],
        'replicate' => [
            'key' => env('REPLICATE_API_KEY'),
            'default_model' => env('REPLICATE_MODEL', 'meta/meta-llama-3-8b-instruct'),
        ],
        'groq' => [
            'key' => env('GROQ_API_KEY'),
            'default_model' => env('GROQ_MODEL', 'llama-3.1-8b-instant'),
        ],
        'nebius' => [
            'key' => env('NEBIUS_API_KEY'),
            'default_model' => env('NEBIUS_MODEL', 'meta-llama/Meta-Llama-3.1-8B-Instruct'),
        ],
    ],
];
