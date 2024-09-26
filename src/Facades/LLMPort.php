<?php

namespace Borah\LLMPort\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Borah\LLMPort\LLMPort
 */
class LLMPort extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Borah\LLMPort\LLMPort::class;
    }
}
