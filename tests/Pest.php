<?php

use Borah\LLMPort\Tests\TestCase;

$dotenv = \Dotenv\Dotenv::createImmutable(realpath(__DIR__.'/../'));
$dotenv->load();

uses(TestCase::class)->in(__DIR__);
