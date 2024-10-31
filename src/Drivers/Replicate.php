<?php

namespace Borah\LLMPort\Drivers;

use BenBjurstrom\Replicate\Replicate as ReplicateClient;
use Borah\LLMPort\Contracts\CanChat;
use Borah\LLMPort\Events\LLMChatResponseReceived;
use Borah\LLMPort\Saloon\Replicate\GetModel;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Support\Facades\Cache;

class Replicate extends LlmProvider implements CanChat
{
    public function chat(ChatRequest $request): ChatResponse
    {
        [$data, $version] = $this->buildParams($request);
        $prediction = $this->client()->predictions()->create($version, $data);

        // poll for prediction to complete
        while ($prediction->status !== 'succeeded' && $prediction->status !== 'failed') {
            usleep(config('llmport.drivers.replicate.poll_interval'));
            $prediction = $this->client()->predictions()->get($prediction->id);
        }

        abort_if($prediction->status === 'failed', 500, 'Failed to get prediction');

        $output = is_array($prediction->output) ? implode('', $prediction->output) : $prediction->output;

        $response = new ChatResponse(
            id: $prediction->id,
            content: mb_trim($output),
            finishReason: 'unknown',
            usage: new ResponseUsage(
                inputTokens: $prediction->metrics['input_token_count'],
                outputTokens: $prediction->metrics['output_token_count'],
            ),
        );

        LLMChatResponseReceived::dispatch($request, $response);

        return $response;
    }

    public function driver(): string
    {
        return 'replicate';
    }

    protected function client(): ReplicateClient
    {
        return new ReplicateClient(config('llmport.drivers.replicate.key'));
    }

    protected function buildParams(ChatRequest $request): array
    {
        $systemMessage = $request->systemMessage();
        $prompt = collect($request->messagesWithoutSystem())
            ->map(fn (ChatMessage $message) => $message->role->value.': '.$message->content)
            ->join("\n\n");

        $version = Cache::remember('replicate-model-version:'.$this->model()->name, now()->addDay(), function () {
            return $this->client()->send(new GetModel($this->model()->name))->json('latest_version.id');
        });
        abort_unless($version, 500, 'Failed to get model version');

        $data = [
            'prompt' => $prompt,
            'system_prompt' => $systemMessage,
            'max_tokens' => $request->maxTokens ?? 512,
            'temperature' => $request->temperature ?? 0.7,
            'top_p' => $request->topP ?? 0.95,
        ];

        if ($request->stop) {
            $data['stop_sequences'] = is_array($request->stop) ? implode(',', $request->stop) : $request->stop;
        }

        return [$data, $version];
    }
}
