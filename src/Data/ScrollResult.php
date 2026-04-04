<?php

declare(strict_types=1);

namespace TheShit\Vector\Data;

final readonly class ScrollResult
{
    /**
     * @param  array<ScoredPoint>  $points
     */
    public function __construct(
        public array $points,
        public string|int|null $nextOffset = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $points = array_map(
            fn (array $p): ScoredPoint => new ScoredPoint(
                id: $p['id'],
                score: 0.0,
                payload: ScoredPoint::normalizePayload($p['payload'] ?? []),
                vector: $p['vector'] ?? null,
            ),
            $data['points'] ?? [],
        );

        return new self(
            points: $points,
            nextOffset: $data['next_page_offset'] ?? null,
        );
    }

    public function hasMore(): bool
    {
        return $this->nextOffset !== null;
    }
}
