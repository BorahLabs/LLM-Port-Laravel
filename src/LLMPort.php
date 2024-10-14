<?php

namespace Borah\LLMPort;

use Borah\LLMPort\Drivers\Anthropic;
use Borah\LLMPort\Drivers\Gemini;
use Borah\LLMPort\Drivers\Groq;
use Borah\LLMPort\Drivers\LlmProvider;
use Borah\LLMPort\Drivers\Nebius;
use Borah\LLMPort\Drivers\OpenAI;
use Borah\LLMPort\Drivers\Replicate;

class LLMPort
{
    protected static array $providers = [];

    public static function register(string $driver, string $provider): void
    {
        if (! class_exists($provider) || ! is_subclass_of($provider, LlmProvider::class)) {
            throw new \Exception("Provider {$provider} is not a valid LLM provider.");
        }

        self::$providers[$driver] = $provider;
    }

    public static function unregister(string $driver): void
    {
        unset(self::$providers[$driver]);
    }

    public function driver(?string $driver = null): LlmProvider
    {
        $driver = $driver ?? config('llmport.default');

        if (isset(self::$providers[$driver])) {
            return new self::$providers[$driver];
        }

        return match ($driver) {
            'openai' => new OpenAI,
            'gemini' => new Gemini,
            'anthropic' => new Anthropic,
            'replicate' => new Replicate,
            'groq' => new Groq,
            'nebius' => new Nebius,
            default => throw new \Exception("Driver {$driver} is not supported. Make sure to register the driver before using it."),
        };
    }

    public function __call($method, $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }

    public static function __callStatic($method, $parameters)
    {
        return (new self)->driver()->$method(...$parameters);
    }
}
