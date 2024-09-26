<?php

namespace Borah\LLMPort\ValueObjects;

class ChatResponse
{
    public function __construct(
        public readonly string $id,
        public readonly string $content,
        public readonly string $finishReason,
        public readonly ?ResponseUsage $usage = null,
    ) {
        //
    }
}
