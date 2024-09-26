<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\ListsModels;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Anthropic implements ListsModels, LlmProvider
{
    protected $models = [
        'claude-3-5-sonnet-20240620',
        'claude-3-opus-20240229',
        'claude-3-sonnet-20240229',
        'claude-3-haiku-20240307',
    ];

    public function models(): Collection
    {
        return collect($this->models)
            ->map(fn (string $model) => new LlmModel(name: $model));
    }

    protected function client(): PendingRequest
    {
        return Http::withHeaders([
            'anthropic-version' => '2023-06-01',
            'x-api-key' => config('llmport.drivers.anthropic.key'),
            'content-type' => 'application/json',
        ])
            ->baseUrl('https://api.anthropic.com/v1');
    }
}
