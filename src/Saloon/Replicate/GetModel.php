<?php

namespace Borah\LLMPort\Saloon\Replicate;

use BenBjurstrom\Replicate\Data\PredictionData;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetModel extends Request
{
    protected Method $method = Method::GET;

    public function __construct(
        protected string $id,
    ) {
    }

    public function resolveEndpoint(): string
    {
        return sprintf('/models/%s', $this->id);
    }
}
