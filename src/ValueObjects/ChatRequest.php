<?php

namespace Borah\LLMPort\ValueObjects;

use Borah\LLMPort\Enums\MessageRole;

class ChatRequest
{
    public function __construct(
        /**
         * @var array<ChatMessage>
         */
        public readonly array $messages,
        public readonly ?float $temperature = null,
        public readonly ?int $maxTokens = null,
        public readonly ?float $topP = null,
        public readonly string|array|null $stop = null,
        public readonly ?string $responseFormat = null,
        public readonly float|int|null $frequencyPenalty = null,
    ) {
        //
    }

    public function messages(): array
    {
        return array_map(fn (ChatMessage $message) => [
            'role' => $message->role->value,
            'content' => $message->content,
        ], $this->messages);
    }

    public function systemMessage(): ?string
    {
        return collect($this->messages)->firstWhere(fn (ChatMessage $message) => $message->role === MessageRole::System)?->content;
    }

    public function messagesWithoutSystem(): array
    {
        return collect($this->messages)->filter(fn (ChatMessage $message) => $message->role !== MessageRole::System)->values()->toArray();
    }
}
