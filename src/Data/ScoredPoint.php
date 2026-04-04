<?php

declare(strict_types=1);

namespace TheShit\Vector\Data;

final readonly class ScoredPoint
{
    /**
     * @param  array<string, mixed>  $payload
     * @param  array<float>|null  $vector
     */
    public function __construct(
        public string|int $id,
        public float $score,
        public array $payload = [],
        public ?array $vector = null,
        public ?int $version = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            score: (float) $data['score'],
            payload: self::normalizePayload($data['payload'] ?? []),
            vector: $data['vector'] ?? null,
            version: $data['version'] ?? null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public static function normalizePayload(array $payload): array
    {
        return array_map(function (mixed $value): mixed {
            if (is_string($value) && str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }

            return $value;
        }, $payload);
    }
}
