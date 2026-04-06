<?php

declare(strict_types=1);

namespace TheShit\Vector;

use TheShit\Vector\Contracts\FilterBuilder;
use TheShit\Vector\Contracts\VectorClient;
use TheShit\Vector\Data\ScoredPoint;
use TheShit\Vector\Filters\QdrantFilter;

final class QueryBuilder
{
    private QdrantFilter $filter;

    private int $queryLimit = 10;

    private ?float $scoreThreshold = null;

    /** @var array<float>|null */
    private ?array $vector = null;

    public function __construct(
        private readonly VectorClient $client,
        private readonly string $collection,
    ) {
        $this->filter = new QdrantFilter;
    }

    public function where(string $key, mixed $value): self
    {
        $this->filter->must($key, $value);

        return $this;
    }

    /**
     * @param  array<mixed>  $values
     */
    public function whereIn(string $key, array $values): self
    {
        $this->filter->mustAny($key, $values);

        return $this;
    }

    public function whereNot(string $key, mixed $value): self
    {
        $this->filter->mustNot($key, $value);

        return $this;
    }

    public function whereRange(string $key, ?float $gte = null, ?float $lte = null, ?float $gt = null, ?float $lt = null): self
    {
        $this->filter->mustRange($key, $gte, $lte, $gt, $lt);

        return $this;
    }

    /**
     * @param  array<float>  $vector
     */
    public function nearVector(array $vector): self
    {
        $this->vector = $vector;

        return $this;
    }

    public function limit(int $limit): self
    {
        $this->queryLimit = $limit;

        return $this;
    }

    public function minScore(float $score): self
    {
        $this->scoreThreshold = $score;

        return $this;
    }

    /**
     * @return array<ScoredPoint>
     */
    public function search(): array
    {
        if ($this->vector === null) {
            throw new \InvalidArgumentException('A vector is required for search. Call nearVector() before search().');
        }

        $filter = $this->filter->toArray() ?: null;

        return $this->client->search(
            collection: $this->collection,
            vector: $this->vector,
            limit: $this->queryLimit,
            filter: $filter,
            scoreThreshold: $this->scoreThreshold,
        );
    }

    public function count(): int
    {
        $filter = $this->filter->toArray() ?: null;

        return $this->client->count(
            collection: $this->collection,
            filter: $filter,
        );
    }

    public function filter(): FilterBuilder
    {
        return $this->filter;
    }
}
