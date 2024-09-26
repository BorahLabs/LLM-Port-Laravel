<?php

namespace Borah\LLMPort\ValueObjects;

class LlmModel
{
    public function __construct(
        public readonly string $name,
    ) {
        //
    }
}
