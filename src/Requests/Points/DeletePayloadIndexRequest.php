<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeletePayloadIndexRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected readonly string $collection,
        protected readonly string $fieldName,
        protected readonly bool $wait = true,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/index/'.$this->fieldName;
    }

    /**
     * @return array<string, string>
     */
    protected function defaultQuery(): array
    {
        return ['wait' => $this->wait ? 'true' : 'false'];
    }
}
