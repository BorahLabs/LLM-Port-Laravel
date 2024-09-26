<?php

namespace Borah\LLMPort\ValueObjects;

class ResponseUsage
{
    public function __construct(
        public int $inputTokens,
        public int $outputTokens,
    ) {}

    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }
}
