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
     * @param  array<string, array<string, mixed>>|null  $sparseVectors
     */
    public function __construct(
        protected readonly string $name,
        protected readonly ?int $size = null,
        protected readonly string $distance = 'Cosine',
        protected readonly ?array $namedVectors = null,
        protected readonly ?array $sparseVectors = null,
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
            $body = ['vectors' => $this->namedVectors];
        } else {
            if ($this->size === null) {
                throw new \InvalidArgumentException(
                    'Collection vector size is required: pass $size or $namedVectors. '
                    .'A silent default dimension would have to match your embedding model by luck.'
                );
            }

            $body = [
                'vectors' => [
                    'size' => $this->size,
                    'distance' => $this->distance,
                ],
            ];
        }

        if ($this->sparseVectors !== null) {
            $body['sparse_vectors'] = $this->sparseVectors;
        }

        return $body;
    }
}
