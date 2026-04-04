<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class GetPointsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string|int>  $ids
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly array $ids,
        protected readonly bool $withPayload = true,
        protected readonly bool $withVector = false,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'ids' => $this->ids,
            'with_payload' => $this->withPayload,
            'with_vector' => $this->withVector,
        ];
    }
}
