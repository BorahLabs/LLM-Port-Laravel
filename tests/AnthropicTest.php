<?php

use Borah\LLMPort\Drivers\Anthropic;
use Borah\LLMPort\ValueObjects\LlmModel;
use Illuminate\Support\Collection;

test('can get models', function () {
    $client = new Anthropic;

    expect($client->models())
        ->toBeInstanceOf(Collection::class)
        ->not->toBeEmpty();

    $client->models()->ensure(LlmModel::class);
});
