<?php

namespace Borah\LLMPort\Traits;

use Closure;
use Psr\Http\Message\StreamInterface;

trait HasHttpStreamingJsonParsing
{
    protected function getStreamedJson(StreamInterface $response, Closure $onOutput)
    {
        $buffer = '';
        $jsonResponse = null;
        while (! $response->eof()) {
            $chunk = $response->read(1);
            $buffer .= $chunk;
            if (json_validate(trim($buffer, ",[]\n\r"))) {
                $jsonResponse = json_decode(trim($buffer, ",[]\n\r"), true);
                $onOutput($jsonResponse);
                $buffer = '';
            } elseif (json_validate($buffer)) {
                $jsonResponse = json_decode($buffer, true);
                $onOutput($jsonResponse);
                $buffer = '';
            }
        }
    }
}
