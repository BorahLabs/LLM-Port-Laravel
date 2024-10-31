<?php

namespace Borah\LLMPort\Events;

use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LLMChatResponseReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly string $driver,
        public readonly LlmModel $model,
        public readonly ChatRequest $request,
        public readonly ChatResponse $response,
    ) {}
}
