<?php

namespace Borah\LLMPort\Drivers;

class Nebius extends OpenAI
{
    public function driver(): string
    {
        return 'nebius';
    }

    protected function getBaseUri(): ?string
    {
        return 'https://api.studio.nebius.ai/v1';
    }

    protected function getApiKey(): ?string
    {
        return config('llmport.drivers.nebius.key');
    }

    protected function getOrganization(): ?string
    {
        return null;
    }
}
