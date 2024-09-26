<?php

namespace Borah\LLMPort\Contracts;

use Illuminate\Support\Collection;

interface ListsModels
{
    public function models(): Collection;
}
