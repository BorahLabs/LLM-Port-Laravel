<?php

namespace Borah\LLMPort\Contracts;

use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;

interface CanChat
{
    public function chat(ChatRequest $request): ChatResponse;
}
