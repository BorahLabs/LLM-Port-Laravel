<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanListModels;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Support\Collection;
use LucianoTonet\GroqPHP\Groq as GroqClient;

class Groq extends LlmProvider implements CanListModels
{
    public function models(): Collection
    {
        return collect($this->client()->models()->list()['data'])
            ->map(fn (array $model) => new LlmModel(name: $model['id']));
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $response = $this->client()->chat()->completions()->create([
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
            id: $response['id'],
            content: $response['choices'][0]['message']['content'],
            finishReason: $response['choices'][0]['finish_reason'],
            usage: new ResponseUsage(inputTokens: $response['usage']['prompt_tokens'], outputTokens: $response['usage']['completion_tokens']),
        );
    }

    public function driver(): string
    {
        return 'groq';
    }

    protected function client(): GroqClient
    {
        return new GroqClient(config('llmport.drivers.groq.key'));
    }
}
