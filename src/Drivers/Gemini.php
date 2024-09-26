<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\ListsModels;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Gemini implements ListsModels, LlmProvider
{
    public function models(): Collection
    {
        return $this->client()
            ->get('/models', [
                'key' => config('llmport.drivers.gemini.key'),
            ])
            ->throw()
            ->collect('models')
            ->map(fn (array $model) => new LlmModel(name: str($model['name'])->after('models/')->value()));
    }

    protected function client(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl('https://generativelanguage.googleapis.com/v1beta');
    }
}
