<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\ListsModels;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Support\Collection;
use OpenAI as GlobalOpenAI;
use OpenAI\Client as OpenAIClient;

class OpenAI implements ListsModels, LlmProvider
{
    public function models(): Collection
    {
        return collect($this->client()->models()->list()->data)
            ->map(fn (GlobalOpenAI\Responses\Models\RetrieveResponse $model) => new LlmModel(name: $model->id));
    }

    protected function getBaseUri(): ?string
    {
        return config('llmport.drivers.openai.base_uri');
    }

    protected function getApiKey(): ?string
    {
        return config('llmport.drivers.openai.key');
    }

    protected function getOrganization(): ?string
    {
        return config('llmport.drivers.openai.organization');
    }

    protected function client(): OpenAIClient
    {
        $baseUri = $this->getBaseUri();
        $apiKey = $this->getApiKey();
        $organization = $this->getOrganization();

        $factory = GlobalOpenAI::factory();

        if ($baseUri) {
            $factory->withBaseUri($baseUri);
        }

        if ($apiKey) {
            $factory->withApiKey($apiKey);
        }

        if ($organization) {
            $factory->withOrganization($organization);
        }

        return $factory->make();
    }
}
