<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanChat;
use Borah\LLMPort\Contracts\CanListModels;
use Borah\LLMPort\Contracts\CanStreamChat;
use Borah\LLMPort\Events\LLMChatResponseReceived;
use Borah\LLMPort\Traits\HasHttpStreamingJsonParsing;
use Borah\LLMPort\Utils\Stream;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class Anthropic extends LlmProvider implements CanChat, CanListModels, CanStreamChat
{
    use HasHttpStreamingJsonParsing;

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
        $start = microtime(true);
        $params = $this->buildParams($request);

        $response = $this->client()
            ->asJson()
            ->post('/messages', $params)
            ->throw()
            ->json();

        $processingTimeInMs = (microtime(true) - $start) * 1000;

        $response = new ChatResponse(
            id: $response['id'],
            content: $response['content'][0]['text'],
            finishReason: $response['stop_reason'],
            usage: new ResponseUsage(
                processingTimeInMs: intval($processingTimeInMs),
                inputTokens: $response['usage']['input_tokens'],
                outputTokens: $response['usage']['output_tokens'],
            ),
        );

        LLMChatResponseReceived::dispatch($this->driver(), $this->model(), $request, $response);

        return $response;
    }

    public function chatStream(ChatRequest $request, Closure $onOutput): ChatResponse
    {
        $start = microtime(true);
        $params = $this->buildParams($request);
        $params['stream'] = true;

        $response = $this->client()
            ->asJson()
            ->post('/messages', $params)
            ->throw();

        $stream = new Stream($response->toPsrResponse());
        $id = null;
        $content = '';
        $inputTokens = 0;
        $outputTokens = 0;
        $stopReason = null;
        foreach ($stream->chunks() as $chunk) {
            if (data_get($chunk, 'message.id')) {
                $id = data_get($chunk, 'message.id');
            }

            if (data_get($chunk, 'message.usage.input_tokens')) {
                $inputTokens = data_get($chunk, 'message.usage.input_tokens');
            }

            if (data_get($chunk, 'message.usage.output_tokens')) {
                $outputTokens = data_get($chunk, 'message.usage.output_tokens');
            }

            if (data_get($chunk, 'delta.stop_reason')) {
                $stopReason = data_get($chunk, 'delta.stop_reason');
            }

            $chunkContent = data_get($chunk, 'delta.text') ?: data_get($chunk, 'content_block.text');
            if (! empty($chunkContent)) {
                $content .= $chunkContent;
                $onOutput($chunkContent, $content);
            }
        }

        $processingTimeInMs = (microtime(true) - $start) * 1000;

        $response = new ChatResponse(
            id: $id,
            content: $content,
            finishReason: $stopReason ?? 'unknown',
            usage: new ResponseUsage(
                processingTimeInMs: intval($processingTimeInMs),
                inputTokens: $inputTokens,
                outputTokens: $outputTokens,
            ),
        );

        LLMChatResponseReceived::dispatch($this->driver(), $this->model(), $request, $response);

        return $response;
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

    protected function buildParams(ChatRequest $request): array
    {
        $systemMessage = $request->systemMessage();
        $messages = $request->messagesWithoutSystem();

        $params = [
            'model' => $this->model()->name,
            'messages' => array_map(fn (ChatMessage $message) => [
                'role' => $message->role->value,
                'content' => $message->content,
            ], $messages),
            'system' => $systemMessage,
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

        return $params;
    }
}
