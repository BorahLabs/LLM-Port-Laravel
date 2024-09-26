<?php

use Borah\LLMPort\Drivers\Gemini;
use Borah\LLMPort\Enums\MessageRole;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\LlmModel;
use Borah\LLMPort\ValueObjects\ResponseUsage;
use Illuminate\Support\Collection;

test('can get models', function () {
    $client = new Gemini;

    expect($client->models())
        ->toBeInstanceOf(Collection::class)
        ->not->toBeEmpty();

    $client->models()->ensure(LlmModel::class);
});

test('can chat', function () {
    $client = new Gemini;

    $response = $client->chat(new ChatRequest(
        messages: [
            new ChatMessage(role: MessageRole::System, content: 'You are an assistant that just replies with Yes or No'),
            new ChatMessage(role: MessageRole::User, content: 'Are you an AI model?'),
        ]
    ));

    expect($response->content)->toContain('Yes');
    expect($response->usage)->toBeInstanceOf(ResponseUsage::class);
    expect($response->usage->inputTokens)->toBeInt()->toBeGreaterThan(0);
    expect($response->usage->outputTokens)->toBeInt()->toBeGreaterThan(0);
});
