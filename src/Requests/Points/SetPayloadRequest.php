<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class SetPayloadRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string|int>  $ids
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly array $ids,
        protected readonly array $payload,
        protected readonly bool $wait = true,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points/payload';
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
        return [
            'payload' => $this->payload,
            'points' => $this->ids,
        ];
    }
}
