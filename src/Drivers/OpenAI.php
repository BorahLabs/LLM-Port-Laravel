<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanListModels;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Support\Collection;
use OpenAI as GlobalOpenAI;
use OpenAI\Client as OpenAIClient;

class OpenAI extends LlmProvider implements CanListModels
{
    public function models(): Collection
    {
        return collect($this->client()->models()->list()->data)
            ->map(fn (GlobalOpenAI\Responses\Models\RetrieveResponse $model) => new LlmModel(name: $model->id));
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $response = $this->client()->chat()->create([
            'model' => $this->model()->name,
            'messages' => $request->messages(),
            'max_tokens' => $request->maxTokens,
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'stop' => $request->stop,
            'response_format' => $request->responseFormat,
            'frequency_penalty' => $request->frequencyPenalty,
        ]);

        return new ChatResponse(
            id: $response->id,
            content: $response->choices[0]->message->content,
            finishReason: $response->choices[0]->finishReason,
            usage: new ResponseUsage(
                inputTokens: $response->usage->promptTokens,
                outputTokens: $response->usage->completionTokens,
            ),
        );
    }

    public function driver(): string
    {
        return 'openai';
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
