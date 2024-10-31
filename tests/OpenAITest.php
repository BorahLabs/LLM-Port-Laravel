<?php

use Borah\LLMPort\Drivers\OpenAI;
use Borah\LLMPort\Enums\MessageRole;
use Borah\LLMPort\Events\LLMChatResponseReceived;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

test('can get models', function () {
    $client = new OpenAI;

    expect($client->models())
        ->toBeInstanceOf(Collection::class)
        ->not->toBeEmpty();

    $client->models()->ensure(LlmModel::class);
});

test('can chat', function () {
    Event::fake();

    $client = new OpenAI;

    $response = $client->chat(new ChatRequest(
        messages: [
            new ChatMessage(role: MessageRole::System, content: 'You are an AI assistant that just replies with Yes or No'),
            new ChatMessage(role: MessageRole::User, content: 'Are you an AI model?'),
        ]
    ));

    expect($response->content)->toContain('Yes');
    expect($response->usage)->toBeInstanceOf(ResponseUsage::class);
    expect($response->usage->inputTokens)->toBeInt()->toBeGreaterThan(0);
    expect($response->usage->outputTokens)->toBeInt()->toBeGreaterThan(0);

    Event::assertDispatched(LLMChatResponseReceived::class);
});

test('can chat stream', function () {
    Event::fake();

    $client = new OpenAI;

    $streamedContent = '';
    $streams = 0;
    $response = $client->chatStream(new ChatRequest(
        messages: [
            new ChatMessage(role: MessageRole::System, content: 'You are an AI assistant that just replies with Yes or No'),
            new ChatMessage(role: MessageRole::User, content: 'Are you an AI model?'),
        ],
        temperature: 0.1,
    ), function (string $delta, string $content) use (&$streamedContent, &$streams) {
        $streamedContent .= $delta;

        expect($content)->toBe($streamedContent);
        $streams++;
    });

    expect($response->content)->toContain('Yes')->toBe($streamedContent);
    expect($streams)->toBeGreaterThan(0);

    Event::assertDispatched(LLMChatResponseReceived::class);
});
