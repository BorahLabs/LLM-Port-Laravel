<?php

namespace Borah\LLMPort\Contracts;

use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Closure;

interface CanStreamChat
{
    public function chatStream(ChatRequest $request, Closure $onOutput): ChatResponse;
}
