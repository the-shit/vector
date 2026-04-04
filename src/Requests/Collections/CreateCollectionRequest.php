<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Collections;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class CreateCollectionRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::PUT;

    /**
     * @param  array<string, array{size: int, distance: string}>|null  $namedVectors
     */
    public function __construct(
        protected readonly string $name,
        protected readonly int $size = 1536,
        protected readonly string $distance = 'Cosine',
        protected readonly ?array $namedVectors = null,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->name;
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        if ($this->namedVectors !== null) {
            return ['vectors' => $this->namedVectors];
        }

        return [
            'vectors' => [
                'size' => $this->size,
                'distance' => $this->distance,
            ],
        ];
    }
}
