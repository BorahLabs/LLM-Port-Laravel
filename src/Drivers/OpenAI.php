<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanChat;
use Borah\LLMPort\Contracts\CanListModels;
use Borah\LLMPort\Contracts\CanStreamChat;
use Borah\LLMPort\Events\LLMChatResponseReceived;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Closure;
use Illuminate\Support\Collection;
use OpenAI as GlobalOpenAI;
use OpenAI\Client as OpenAIClient;

class OpenAI extends LlmProvider implements CanChat, CanListModels, CanStreamChat
{
    public function models(): Collection
    {
        return collect($this->client()->models()->list()->data)
            ->map(fn (GlobalOpenAI\Responses\Models\RetrieveResponse $model) => new LlmModel(name: $model->id));
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $start = microtime(true);
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

        $processingTimeInMs = (microtime(true) - $start) * 1000;

        $response = new ChatResponse(
            id: $response->id,
            content: $response->choices[0]->message->content,
            finishReason: $response->choices[0]->finishReason,
            usage: new ResponseUsage(
                processingTimeInMs: intval($processingTimeInMs),
                inputTokens: $response->usage->promptTokens,
                outputTokens: $response->usage->completionTokens,
            ),
        );

        LLMChatResponseReceived::dispatch($this->driver(), $this->model(), $request, $response, $request->metadata);

        return $response;
    }

    public function chatStream(ChatRequest $request, Closure $onOutput): ChatResponse
    {
        $start = microtime(true);
        $response = $this->client()->chat()->createStreamed([
            'model' => $this->model()->name,
            'messages' => $request->messages(),
            'max_tokens' => $request->maxTokens,
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'stop' => $request->stop,
            'response_format' => $request->responseFormat,
            'frequency_penalty' => $request->frequencyPenalty,
            'stream_options' => [
                'include_usage' => true,
            ],
        ]);

        $id = null;
        $content = null;
        $finishReason = null;
        $inputTokens = null;
        $outputTokens = null;
        $processingTimeInMs = null;

        foreach ($response as $chunk) {
            if (is_null($id)) {
                $id = $chunk->id;
            }

            if (! empty($chunk->choices)) {
                if ($chunk->choices[0]->delta->content) {
                    $content .= $chunk->choices[0]->delta->content;
                    $onOutput($chunk->choices[0]->delta->content, $content);
                } elseif ($chunk->choices[0]->finishReason) {
                    $finishReason = $chunk->choices[0]->finishReason;
                }
            }

            if ($chunk->usage) {
                $inputTokens = $chunk->usage->promptTokens;
                $outputTokens = $chunk->usage->completionTokens;
            }
        }

        $processingTimeInMs = (microtime(true) - $start) * 1000;

        $response = new ChatResponse(
            id: $id,
            content: $content,
            finishReason: $finishReason ?? 'unknown',
            usage: new ResponseUsage(
                processingTimeInMs: intval($processingTimeInMs),
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
            ),
        );

        LLMChatResponseReceived::dispatch($this->driver(), $this->model(), $request, $response, $request->metadata);

        return $response;
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
