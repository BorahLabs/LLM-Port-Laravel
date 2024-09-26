<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanListModels;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Anthropic extends LlmProvider implements CanListModels
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

    public function chat(ChatRequest $request): ChatResponse
    {
        $systemMessage = $request->systemMessage();
        $messages = $request->messagesWithoutSystem();

        $params = [
            'model' => $this->model()->name,
            'messages' => array_map(fn (ChatMessage $message) => [
                'role' => $message->role->value,
                'content' => $message->content,
            ], $messages),
            'max_tokens' => $request->maxTokens ?? 512,
        ];

        if ($request->temperature) {
            $params['temperature'] = $request->temperature;
        }

        if ($request->topP) {
            $params['top_p'] = $request->topP;
        }

        if ($request->stop) {
            $stop = is_array($request->stop) ? $request->stop : [$request->stop];
            $params['stop_sequences'] = $stop;
        }

        $response = $this->client()
            ->asJson()
            ->post('/messages', $params)
            ->throw()
            ->json();

        return new ChatResponse(
            id: $response['id'],
            content: $response['content'][0]['text'],
            finishReason: $response['stop_reason'],
            usage: new ResponseUsage(
                inputTokens: $response['usage']['input_tokens'],
                outputTokens: $response['usage']['output_tokens'],
            ),
        );
    }

    public function driver(): string
    {
        return 'anthropic';
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
