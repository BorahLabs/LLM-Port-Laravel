<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\ListsModels;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Support\Collection;
use LucianoTonet\GroqPHP\Groq as GroqClient;

class Groq implements ListsModels, LlmProvider
{
    public function models(): Collection
    {
        return collect($this->client()->models()->list()['data'])
            ->map(fn (array $model) => new LlmModel(name: $model['id']));
    }

    protected function client(): GroqClient
    {
        return new GroqClient(config('llmport.drivers.groq.key'));
    }
}
