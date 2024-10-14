<?php

use Borah\LLMPort\Drivers\Anthropic;
use Borah\LLMPort\Drivers\Gemini;
use Borah\LLMPort\Drivers\Groq;
use Borah\LLMPort\Drivers\Nebius;
use Borah\LLMPort\Drivers\OpenAI;
use Borah\LLMPort\Drivers\Replicate;
use Borah\LLMPort\Enums\MessageRole;
use Borah\LLMPort\Facades\LLMPort;
use Borah\LLMPort\ValueObjects\ChatMessage;
use Borah\LLMPort\ValueObjects\ChatRequest;
use Borah\LLMPort\ValueObjects\ResponseUsage;

it('can register a provider', function () {
  expect(LLMPort::driver('openai'))->toBeInstanceOf(OpenAI::class);

  LLMPort::register('openai', Groq::class);

  expect(LLMPort::driver('openai'))->toBeInstanceOf(Groq::class);

  LLMPort::unregister('openai');

  expect(LLMPort::driver('openai'))->toBeInstanceOf(OpenAI::class);
});

it('can instantiate Anthropic', function () {
  expect(LLMPort::driver('anthropic'))->toBeInstanceOf(Anthropic::class);
});

it('can instantiate Nebius', function () {
  expect(LLMPort::driver('nebius'))->toBeInstanceOf(Nebius::class);
});

it('can instantiate Replicate', function () {
  expect(LLMPort::driver('replicate'))->toBeInstanceOf(Replicate::class);
});

it('can instantiate OpenAI', function () {
  expect(LLMPort::driver('openai'))->toBeInstanceOf(OpenAI::class);
});

it('can instantiate Groq', function () {
  expect(LLMPort::driver('groq'))->toBeInstanceOf(Groq::class);
});

it('can instantiate Gemini', function () {
  expect(LLMPort::driver('gemini'))->toBeInstanceOf(Gemini::class);
});

it('can be used without specific driver', function () {
  $response = LLMPort::chat(new ChatRequest(
    messages: [
        new ChatMessage(role: MessageRole::System, content: 'You are an AI assistant that just replies with Yes or No'),
        new ChatMessage(role: MessageRole::User, content: 'Are you an AI model?'),
    ]
  ));

  expect($response->content)->toContain('Yes');
  expect($response->usage)->toBeInstanceOf(ResponseUsage::class);
  expect($response->usage->inputTokens)->toBeInt()->toBeGreaterThan(0);
  expect($response->usage->outputTokens)->toBeInt()->toBeGreaterThan(0);
});
