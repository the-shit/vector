<?php

declare(strict_types=1);

namespace TheShit\Vector;

use TheShit\Vector\Contracts\VectorClient;
use TheShit\Vector\Data\CollectionInfo;
use TheShit\Vector\Data\Point;
use TheShit\Vector\Data\ScoredPoint;
use TheShit\Vector\Data\ScrollResult;
use TheShit\Vector\Data\UpsertResult;
use TheShit\Vector\Requests\Collections\CreateCollectionRequest;
use TheShit\Vector\Requests\Collections\DeleteCollectionRequest;
use TheShit\Vector\Requests\Collections\GetCollectionRequest;
use TheShit\Vector\Requests\Points\DeletePointsRequest;
use TheShit\Vector\Requests\Points\GetPointsRequest;
use TheShit\Vector\Requests\Points\ScrollPointsRequest;
use TheShit\Vector\Requests\Points\SearchPointsRequest;
use TheShit\Vector\Requests\Points\UpsertPointsRequest;

class Qdrant implements VectorClient
{
    public function __construct(
        protected readonly QdrantConnector $connector,
    ) {}

    public function createCollection(string $name, int $size, string $distance = 'Cosine'): bool
    {
        $response = $this->connector->send(new CreateCollectionRequest($name, $size, $distance));
        $response->throw();

        return $response->json('result') === true;
    }

    public function deleteCollection(string $name): bool
    {
        $response = $this->connector->send(new DeleteCollectionRequest($name));
        $response->throw();

        return $response->json('result') === true;
    }

    public function getCollection(string $name): CollectionInfo
    {
        $response = $this->connector->send(new GetCollectionRequest($name));
        $response->throw();

        return CollectionInfo::fromArray($response->json('result'));
    }

    /**
     * @param  array<string|int>  $ids
     * @return array<ScoredPoint>
     */
    public function getPoints(string $collection, array $ids): array
    {
        $response = $this->connector->send(new GetPointsRequest($collection, $ids));
        $response->throw();

        return array_map(
            fn (array $p): ScoredPoint => new ScoredPoint(
                id: $p['id'],
                score: 0.0,
                payload: $p['payload'] ?? [],
                vector: $p['vector'] ?? null,
            ),
            $response->json('result') ?? [],
        );
    }

    /**
     * @param  array<int, array{id: string|int, vector: array<float>, payload?: array<string, mixed>}>|array<Point>  $points
     */
    public function upsert(string $collection, array $points, bool $wait = true): UpsertResult
    {
        $normalized = array_map(
            fn (array|Point $p): array => $p instanceof Point ? $p->toArray() : $p,
            $points,
        );

        $response = $this->connector->send(new UpsertPointsRequest($collection, $normalized, $wait));
        $response->throw();

        return UpsertResult::fromArray($response->json('result'));
    }

    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>|null  $filter
     * @return array<ScoredPoint>
     */
    public function search(string $collection, array $vector, int $limit = 10, ?array $filter = null): array
    {
        $response = $this->connector->send(new SearchPointsRequest($collection, $vector, $limit, $filter));
        $response->throw();

        return array_map(
            fn (array $p): ScoredPoint => ScoredPoint::fromArray($p),
            $response->json('result') ?? [],
        );
    }

    /**
     * @param  array<string, mixed>|null  $filter
     */
    public function scroll(string $collection, int $limit = 100, ?array $filter = null, string|int|null $offset = null): ScrollResult
    {
        $response = $this->connector->send(new ScrollPointsRequest($collection, $limit, $filter, $offset));
        $response->throw();

        return ScrollResult::fromArray($response->json('result'));
    }

    /**
     * @param  array<string|int>|null  $ids
     * @param  array<string, mixed>|null  $filter
     */
    public function delete(string $collection, ?array $ids = null, ?array $filter = null, bool $wait = true): UpsertResult
    {
        $response = $this->connector->send(new DeletePointsRequest($collection, $ids, $filter, $wait));
        $response->throw();

        return UpsertResult::fromArray($response->json('result'));
    }

    public function connector(): QdrantConnector
    {
        return $this->connector;
    }
}
