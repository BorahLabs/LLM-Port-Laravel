<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanChat;
use Borah\LLMPort\Contracts\CanListModels;
use Borah\LLMPort\Contracts\CanStreamChat;
use Borah\LLMPort\Enums\MessageRole;
use Borah\LLMPort\Traits\HasHttpStreamingJsonParsing;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ChatResponse;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Closure;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class Gemini extends LlmProvider implements CanChat, CanListModels, CanStreamChat
{
    use HasHttpStreamingJsonParsing;

    public function models(): Collection
    {
        return $this->client()
            ->get('/models', [
                'key' => config('llmport.drivers.gemini.key'),
            ])
            ->throw()
            ->collect('models')
            ->map(fn (array $model) => new LlmModel(name: str($model['name'])->after('models/')->value()));
    }

    public function chat(ChatRequest $request): ChatResponse
    {
        $params = $this->buildParams($request);

        $response = $this->client()
            ->asJson()
            ->withQueryParameters(['key' => config('llmport.drivers.gemini.key')])
            ->post('/models/'.$this->model()->name.':generateContent', $params)
            ->throw()
            ->json();

        return new ChatResponse(
            id: Str::uuid(),
            content: $response['candidates'][0]['content']['parts'][0]['text'],
            finishReason: mb_strtolower($response['candidates'][0]['finishReason']),
            usage: new ResponseUsage(
                inputTokens: $response['usageMetadata']['promptTokenCount'],
                outputTokens: $response['usageMetadata']['candidatesTokenCount'],
            ),
        );
    }

    public function chatStream(ChatRequest $request, Closure $onOutput): ChatResponse
    {
        $params = $this->buildParams($request);

        $response = $this->client()
            ->asJson()
            ->withQueryParameters(['key' => config('llmport.drivers.gemini.key')])
            ->withOptions(['stream' => true])
            ->post('/models/'.$this->model()->name.':streamGenerateContent', $params)
            ->throw()
            ->getBody();

        $fullContent = '';
        $jsonResponse = null;
        $this->getStreamedJson($response, function ($json) use (&$fullContent, &$jsonResponse, $onOutput) {
            $jsonResponse = $json;
            $fullContent .= $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
            $onOutput($jsonResponse['candidates'][0]['content']['parts'][0]['text'], $fullContent);
        });

        return new ChatResponse(
            id: Str::uuid(),
            content: $fullContent,
            finishReason: mb_strtolower($jsonResponse['candidates'][0]['finishReason']),
            usage: new ResponseUsage(
                inputTokens: $jsonResponse['usageMetadata']['promptTokenCount'],
                outputTokens: $jsonResponse['usageMetadata']['candidatesTokenCount'],
            ),
        );
    }

    public function driver(): string
    {
        return 'gemini';
    }

    protected function client(): PendingRequest
    {
        return Http::acceptJson()
            ->baseUrl('https://generativelanguage.googleapis.com/v1beta');
    }

    protected function buildParams(ChatRequest $request): array
    {
        $systemMessage = $request->systemMessage();
        $messages = $request->messagesWithoutSystem();
        $params = [
            'contents' => [],
        ];

        if ($systemMessage) {
            $params['systemInstruction'] = [
                'parts' => ['text' => $systemMessage],
            ];
        }

        foreach ($messages as $message) {
            $params['contents'][] = [
                'role' => $message->role === MessageRole::User ? 'user' : 'model',
                'parts' => [
                    ['text' => $message->content],
                ],
            ];
        }

        $params['generationConfig'] = [];
        if ($request->temperature) {
            $params['generationConfig']['temperature'] = $request->temperature;
        }

        if ($request->maxTokens) {
            $params['generationConfig']['maxOutputTokens'] = $request->maxTokens;
        }

        if ($request->topP) {
            $params['generationConfig']['topP'] = $request->topP;
        }

        if ($request->stop) {
            $stop = is_array($request->stop) ? $request->stop : [$request->stop];
            $params['generationConfig']['stopSequences'] = $stop;
        }

        if ($request->frequencyPenalty) {
            $params['generationConfig']['frequencyPenalty'] = $request->frequencyPenalty;
        }

        if (empty($params['generationConfig'])) {
            unset($params['generationConfig']);
        }

        return $params;
    }
}
