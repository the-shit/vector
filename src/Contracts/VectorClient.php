<?php

declare(strict_types=1);

namespace TheShit\Vector\Contracts;

use TheShit\Vector\Data\CollectionInfo;
use TheShit\Vector\Data\ScoredPoint;
use TheShit\Vector\Data\ScrollResult;
use TheShit\Vector\Data\UpsertResult;

interface VectorClient
{
    public function createCollection(string $name, int $size, string $distance = 'Cosine'): bool;

    public function deleteCollection(string $name): bool;

    public function getCollection(string $name): CollectionInfo;

    /**
     * @param  array<string|int>  $ids
     * @return array<ScoredPoint>
     */
    public function getPoints(string $collection, array $ids): array;

    /**
     * @param  array<int, array{id: string|int, vector: array<float>, payload?: array<string, mixed>}>  $points
     */
    public function upsert(string $collection, array $points, bool $wait = true): UpsertResult;

    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>|null  $filter
     * @return array<ScoredPoint>
     */
    public function search(string $collection, array $vector, int $limit = 10, ?array $filter = null): array;

    /**
     * @param  array<string, mixed>|null  $filter
     */
    public function scroll(string $collection, int $limit = 100, ?array $filter = null, string|int|null $offset = null): ScrollResult;

    /**
     * @param  array<string|int>  $ids
     * @param  array<string, mixed>  $payload
     */
    public function setPayload(string $collection, array $ids, array $payload, bool $wait = true): UpsertResult;

    /**
     * @param  array<string, mixed>|null  $filter
     */
    public function count(string $collection, ?array $filter = null): int;

    public function createPayloadIndex(string $collection, string $fieldName, ?string $fieldSchema = null): UpsertResult;

    public function deletePayloadIndex(string $collection, string $fieldName): UpsertResult;

    /**
     * @param  array<string|int>|null  $ids
     * @param  array<string, mixed>|null  $filter
     */
    public function delete(string $collection, ?array $ids = null, ?array $filter = null, bool $wait = true): UpsertResult;
}
