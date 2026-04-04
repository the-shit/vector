<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class UpsertPointsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    /**
     * @param  array<int, array{id: string|int, vector: array<float>, payload?: array<string, mixed>}>  $points
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly array $points,
        protected readonly bool $wait = true,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points';
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return ['wait' => $this->wait ? 'true' : 'false'];
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return ['points' => $this->points];
    }
}
