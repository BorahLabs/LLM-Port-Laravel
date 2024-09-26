<?php

namespace Borah\LLMPort\Drivers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class Replicate implements LlmProvider
{
    protected function client(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl('https://generativelanguage.googleapis.com/v1beta');
    }
}
