<?php

declare(strict_types=1);

namespace TheShit\Vector\Data;

final readonly class CollectionInfo
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public string $status,
        public int $pointsCount,
        public int $indexedVectorsCount,
        public int $segmentsCount,
        public array $config = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? 'unknown',
            pointsCount: $data['points_count'] ?? 0,
            indexedVectorsCount: $data['indexed_vectors_count'] ?? 0,
            segmentsCount: $data['segments_count'] ?? 0,
            config: $data['config'] ?? [],
        );
    }
}
