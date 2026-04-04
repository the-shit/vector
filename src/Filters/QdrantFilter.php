<?php

declare(strict_types=1);

namespace TheShit\Vector\Filters;

use TheShit\Vector\Contracts\FilterBuilder;

final class QdrantFilter implements FilterBuilder
{
    /** @var array<int, array<string, mixed>> */
    private array $must = [];

    /** @var array<int, array<string, mixed>> */
    private array $mustNot = [];

    /** @var array<int, array<string, mixed>> */
    private array $should = [];

    public static function where(string $key, mixed $value): self
    {
        return (new self)->must($key, $value);
    }

    public function must(string $key, mixed $value): self
    {
        $this->must[] = ['key' => $key, 'match' => ['value' => $value]];

        return $this;
    }

    /**
     * @param  array<mixed>  $values
     */
    public function mustAny(string $key, array $values): self
    {
        $this->must[] = ['key' => $key, 'match' => ['any' => $values]];

        return $this;
    }

    public function mustNot(string $key, mixed $value): self
    {
        $this->mustNot[] = ['key' => $key, 'match' => ['value' => $value]];

        return $this;
    }

    public function mustRange(string $key, ?float $gte = null, ?float $lte = null, ?float $gt = null, ?float $lt = null): self
    {
        $range = array_filter([
            'gte' => $gte,
            'lte' => $lte,
            'gt' => $gt,
            'lt' => $lt,
        ], fn (?float $v): bool => $v !== null);

        $this->must[] = ['key' => $key, 'range' => $range];

        return $this;
    }

    public function should(string $key, mixed $value): self
    {
        $this->should[] = ['key' => $key, 'match' => ['value' => $value]];

        return $this;
    }

    public function fullText(string $key, string $query): self
    {
        $this->must[] = ['key' => $key, 'match' => ['text' => $query]];

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'must' => $this->must ?: null,
            'must_not' => $this->mustNot ?: null,
            'should' => $this->should ?: null,
        ], fn ($v): bool => $v !== null);
    }
}
