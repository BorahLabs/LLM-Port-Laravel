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
use LucianoTonet\GroqPHP\Groq as GroqClient;

class Groq extends LlmProvider implements CanChat, CanListModels, CanStreamChat
{
    public function models(): Collection
    {
        return collect($this->client()->models()->list()['data'])
            ->map(fn (array $model) => new LlmModel(name: $model['id']));
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $start = microtime(true);
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

        $processingTimeInMs = (microtime(true) - $start) * 1000;

        $response = new ChatResponse(
            id: $response['id'],
            content: $response['choices'][0]['message']['content'],
            finishReason: $response['choices'][0]['finish_reason'],
            usage: new ResponseUsage(
                processingTimeInMs: intval($processingTimeInMs),
                inputTokens: $response['usage']['prompt_tokens'],
                outputTokens: $response['usage']['completion_tokens']
            ),
        );

        LLMChatResponseReceived::dispatch($this->driver(), $this->model(), $request, $response, $request->metadata);

        return $response;
    }

    public function chatStream(ChatRequest $request, Closure $onOutput): ChatResponse
    {
        $start = microtime(true);
        $response = $this->client()->chat()->completions()->create([
            'model' => $this->model()->name,
            'messages' => $request->messages(),
            'max_tokens' => $request->maxTokens,
            'temperature' => $request->temperature,
            'top_p' => $request->topP,
            'stop' => $request->stop,
            'response_format' => $request->responseFormat,
            'frequency_penalty' => $request->frequencyPenalty,
            'stream' => true,
        ]);

        $id = null;
        $content = null;
        $finishReason = null;
        $inputTokens = null;
        $outputTokens = null;

        foreach ($response->chunks() as $chunk) {
            if (is_null($id)) {
                $id = $chunk['id'];
            }

            if (isset($chunk['choices'][0]['delta']['content'])) {
                $content .= $chunk['choices'][0]['delta']['content'];
                $onOutput($chunk['choices'][0]['delta']['content'], $content);
            } elseif (isset($chunk['choices'][0]['finish_reason'])) {
                $finishReason = $chunk['choices'][0]['finish_reason'];
            }

            if (data_get($chunk, 'x_groq.usage.prompt_tokens')) {
                $inputTokens = intval(data_get($chunk, 'x_groq.usage.prompt_tokens'));
            }

            if (data_get($chunk, 'x_groq.usage.completion_tokens')) {
                $outputTokens = intval(data_get($chunk, 'x_groq.usage.completion_tokens'));
            }
        }

        $processingTimeInMs = (microtime(true) - $start) * 1000;

        $response = new ChatResponse(
            id: $id,
            content: $content,
            finishReason: $finishReason,
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
        return 'groq';
    }

    protected function client(): GroqClient
    {
        return new GroqClient(config('llmport.drivers.groq.key'));
    }
}
