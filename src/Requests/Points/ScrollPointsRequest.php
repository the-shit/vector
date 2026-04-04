<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class ScrollPointsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string, mixed>|null  $filter
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly int $limit = 100,
        protected readonly ?array $filter = null,
        protected readonly string|int|null $offset = null,
        protected readonly bool $withPayload = true,
        protected readonly bool $withVector = false,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points/scroll';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'limit' => $this->limit,
            'offset' => $this->offset,
            'with_payload' => $this->withPayload,
            'with_vector' => $this->withVector,
            'filter' => $this->filter,
        ], fn (string|int|bool|array|null $v): bool => $v !== null);
    }
}
