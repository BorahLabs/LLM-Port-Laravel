<?php

use Borah\LLMPort\Drivers\Gemini;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Support\Collection;

test('can get models', function () {
    $client = new Gemini;

    expect($client->models())
        ->toBeInstanceOf(Collection::class)
        ->not->toBeEmpty();

    $client->models()->ensure(LlmModel::class);
});
