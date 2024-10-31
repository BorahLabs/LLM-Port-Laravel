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

        $response = new ChatResponse(
            id: $response['id'],
            content: $response['choices'][0]['message']['content'],
            finishReason: $response['choices'][0]['finish_reason'],
            usage: new ResponseUsage(inputTokens: $response['usage']['prompt_tokens'], outputTokens: $response['usage']['completion_tokens']),
        );

        LLMChatResponseReceived::dispatch($request, $response);

        return $response;
    }

    public function chatStream(ChatRequest $request, Closure $onOutput): ChatResponse
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
            'stream' => true,
        ]);

        $id = null;
        $content = null;
        $finishReason = null;

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
        }

        $response = new ChatResponse(
            id: $id,
            content: $content,
            finishReason: $finishReason,
            usage: null,
        );

        LLMChatResponseReceived::dispatch($request, $response);

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
