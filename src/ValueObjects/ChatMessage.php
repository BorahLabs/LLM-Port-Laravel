<?php

namespace Borah\LLMPort\ValueObjects;

use Borah\LLMPort\Enums\MessageRole;

class ChatMessage
{
    public function __construct(
        public readonly MessageRole $role,
        public readonly string $content,
    ) {
        //
    }
}
