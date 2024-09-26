<?php

namespace Borah\LLMPort\Contracts;

use Illuminate\Support\Collection;

interface CanListModels
{
    public function models(): Collection;
}
