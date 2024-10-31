<?php

use Borah\LLMPort\Drivers\Replicate;
use Borah\LLMPort\Enums\MessageRole;
use Borah\LLMPort\Events\LLMChatResponseReceived;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Support\Facades\Event;

test('can chat', function () {
    Event::fake();

    $client = new Replicate;

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
