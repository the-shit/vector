<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class DeletePointsRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string|int>|null  $ids
     * @param  array<string, mixed>|null  $filter
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly ?array $ids = null,
        protected readonly ?array $filter = null,
        protected readonly bool $wait = true,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points/delete';
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
        if ($this->ids !== null) {
            return ['points' => $this->ids];
        }

        return ['filter' => $this->filter ?? []];
    }
}
