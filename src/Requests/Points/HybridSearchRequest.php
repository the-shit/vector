<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Points;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class HybridSearchRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<float>  $denseVector
     * @param  array{indices: array<int>, values: array<float>}  $sparseVector
     * @param  array<string, mixed>|null  $filter
     */
    public function __construct(
        protected readonly string $collection,
        protected readonly array $denseVector,
        protected readonly array $sparseVector,
        protected readonly string $denseVectorName = 'dense',
        protected readonly string $sparseVectorName = 'sparse',
        protected readonly int $limit = 10,
        protected readonly ?array $filter = null,
        protected readonly bool $withPayload = true,
        protected readonly bool $withVector = false,
        protected readonly string $fusion = 'rrf',
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->collection.'/points/query';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        $prefetchDense = [
            'query' => $this->denseVector,
            'using' => $this->denseVectorName,
            'limit' => $this->limit,
        ];

        $prefetchSparse = [
            'query' => [
                'indices' => $this->sparseVector['indices'],
                'values' => $this->sparseVector['values'],
            ],
            'using' => $this->sparseVectorName,
            'limit' => $this->limit,
        ];

        if ($this->filter !== null) {
            $prefetchDense['filter'] = $this->filter;
            $prefetchSparse['filter'] = $this->filter;
        }

        return array_filter([
            'prefetch' => [$prefetchDense, $prefetchSparse],
            'query' => ['fusion' => $this->fusion],
            'limit' => $this->limit,
            'with_payload' => $this->withPayload,
            'with_vector' => $this->withVector,
            'filter' => $this->filter,
        ], fn (string|int|bool|array|null $v): bool => $v !== null);
    }
}
