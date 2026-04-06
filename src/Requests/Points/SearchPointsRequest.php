<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SearchPointsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>|null  $filter
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly array $vector,
        protected readonly int $limit = 10,
        protected readonly ?array $filter = null,
        protected readonly bool $withPayload = true,
        protected readonly bool $withVector = false,
        protected readonly ?float $scoreThreshold = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points/search';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return array_filter([
            'vector' => $this->vector,
            'limit' => $this->limit,
            'with_payload' => $this->withPayload,
            'with_vector' => $this->withVector,
            'filter' => $this->filter,
            'score_threshold' => $this->scoreThreshold,
        ], fn (int|bool|array|float|null $v): bool => $v !== null);
    }
}
