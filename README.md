# LLM Port

[![Latest Version on Packagist](https://img.shields.io/packagist/v/borahlabs/llm-port-laravel.svg?style=flat-square)](https://packagist.org/packages/borah/llm-port-laravel)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/borahlabs/llm-port-laravel/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/borahlabs/llm-port-laravel/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/borahlabs/llm-port-laravel/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/borahlabs/llm-port-laravel/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/borahlabs/llm-port-laravel.svg?style=flat-square)](https://packagist.org/packages/borah/llm-port-laravel)

Wrapper around the most popular LLMs that allows drop-in replacement of large language models in Laravel.

## Installation

You can install the package via composer:

```bash
composer require borah/llm-port-laravel
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="llm-port-laravel-config"
```

This is the contents of the published config file:

```php
return [
    'default' => env('LLMPORT_DEFAULT_DRIVER', 'openai'),
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
            'poll_interval' => env('REPLICATE_POLL_INTERVAL', 100000),
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

```

## Usage

```php
use Borah\LLMPort\Facades\LLMPort;
use Borah\LLMPort\Enums\MessageRole;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;

$response = LLMPort::chat(new ChatRequest(
    messages: [
        new ChatMessage(role: MessageRole::System, content: 'You are an AI assistant that just replies with Yes or No'),
        new ChatMessage(role: MessageRole::User, content: 'Are you an AI model?'),
    ]
));

echo $response->id; // 'chatcmpl-...'
echo $response->content; // 'Yes'
echo $response->finishReason; // 'stop'
echo $response->usage?->inputTokens; // 5
echo $response->usage?->outputTokens; // 10
echo $response->usage?->totalTokens(); // 15
```

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
