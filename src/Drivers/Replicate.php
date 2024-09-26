<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use BenBjurstrom\Replicate\Replicate as ReplicateClient;
use Borah\LLMPort\ValueObjects\ChatMessage;

class Replicate extends LlmProvider
{
    public function chat(ChatRequest $request): ChatResponse
    {
        $systemMessage = $request->systemMessage();
        $prompt = collect($request->messagesWithoutSystem())
            ->map(fn (ChatMessage $message) => $message->role->value . ': ' . $message->content)
            ->join("\n\n");

        $response = $this->client()->predictions()->create($this->model()->name, [
            'input' => [
                'prompt' => $prompt,
                'system_prompt' => $systemMessage,
                'max_tokens' => $request->maxTokens ?? 512,
                'stop_sequences' => is_array($request->stop) ? join(',', $request->stop) : $request->stop,
                'temperature' => $request->temperature ?? 0.7,
                'top_p' => $request->topP ?? 0.95,
            ],
        ]);

        dd($response);
    }

    public function driver(): string
    {
        return 'replicate';
    }

    protected function client(): ReplicateClient
    {
        return new ReplicateClient(config('llmport.drivers.replicate.key'));
    }
}
