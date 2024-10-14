<?php

namespace Borah\LLMPort\Traits;

use Closure;
use GuzzleHttp\Psr7\Stream;

trait HasHttpStreamingJsonParsing
{
    protected function getStreamedJson(Stream $response, Closure $onOutput)
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
