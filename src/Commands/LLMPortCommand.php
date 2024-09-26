<?php

namespace Borah\LLMPort\Commands;

use Illuminate\Console\Command;

class LLMPortCommand extends Command
{
    public $signature = 'llm-port-laravel';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
