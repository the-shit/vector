<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreatePayloadIndexRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    public function __construct(
        protected readonly string $collection,
        protected readonly string $fieldName,
        protected readonly ?string $fieldSchema = null,
        protected readonly bool $wait = true,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/index';
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
        $body = ['field_name' => $this->fieldName];

        if ($this->fieldSchema !== null) {
            $body['field_schema'] = $this->fieldSchema;
        }

        return $body;
    }
}
