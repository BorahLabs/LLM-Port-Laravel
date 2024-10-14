<?php

namespace Borah\LLMPort\Facades;

use Borah\LLMPort\Drivers\LlmProvider;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void register(string $driver, string $provider)
 * @method static void unregister(string $driver)
 * @method static LlmProvider driver(string $driver)
 * @method static LlmModel model(string $model)
 * @method static LlmProvider using(string|LlmModel $model)
 * @method static ChatResponse chat(ChatRequest $request)
 * @method static Collection models()
 * @method static ChatResponse chatStream(ChatRequest $request, Closure $onOutput): ChatResponse;
 *
 * @see \Borah\LLMPort\LLMPort
 */
class LLMPort extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Borah\LLMPort\LLMPort::class;
    }
}
