<?php

namespace Borah\LLMPort\Drivers;

use Borah\LLMPort\Contracts\CanChat;
use Borah\LLMPort\ValueObjects\LlmModel;

abstract class LlmProvider implements CanChat
{
    protected ?LlmModel $model;

    public function using(string|LlmModel $model): static
    {
        if (is_string($model)) {
            $model = new LlmModel($model);
        }

        $this->model = $model;

        return $this;
    }

    public function driver(): ?string
    {
        return config('llmport.default');
    }

    public function model(): LlmModel
    {
        abort_if(! config('llmport.drivers.'.$this->driver().'.default_model'), 500, 'Default model not set for '.$this->driver());

        return $this->model ?? new LlmModel(config('llmport.drivers.'.$this->driver().'.default_model'));
    }
}
