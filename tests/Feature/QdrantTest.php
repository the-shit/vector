<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TheShit\Vector\Data\CollectionInfo;
use TheShit\Vector\Data\Point;
use TheShit\Vector\Data\ScoredPoint;
use TheShit\Vector\Data\ScrollResult;
use TheShit\Vector\Data\UpsertResult;
use TheShit\Vector\Qdrant;
use TheShit\Vector\QdrantConnector;
use TheShit\Vector\Requests\Collections\CreateCollectionRequest;
use TheShit\Vector\Requests\Collections\DeleteCollectionRequest;
use TheShit\Vector\Requests\Collections\GetCollectionRequest;
use TheShit\Vector\Requests\Points\CountPointsRequest;
use TheShit\Vector\Requests\Points\CreatePayloadIndexRequest;
use TheShit\Vector\Requests\Points\DeletePayloadIndexRequest;
use TheShit\Vector\Requests\Points\DeletePointsRequest;
use TheShit\Vector\Requests\Points\GetPointsRequest;
use TheShit\Vector\Requests\Points\ScrollPointsRequest;
use TheShit\Vector\Requests\Points\SearchPointsRequest;
use TheShit\Vector\Requests\Points\SetPayloadRequest;
use TheShit\Vector\Requests\Points\UpsertPointsRequest;

function makeClient(MockClient $mock): Qdrant
{
    $connector = new QdrantConnector('http://localhost:6333', 'test-key');
    $connector->withMockClient($mock);

    return new Qdrant($connector);
}

describe('Qdrant::createCollection', function (): void {
    it('creates a collection and returns true', function (): void {
        $mock = new MockClient([
            CreateCollectionRequest::class => MockResponse::make(
                ['result' => true, 'status' => 'ok', 'time' => 0.01],
            ),
        ]);

        $result = makeClient($mock)->createCollection('test', 1536);

        expect($result)->toBeTrue();
        $mock->assertSent(CreateCollectionRequest::class);
    });
});

describe('Qdrant::deleteCollection', function (): void {
    it('deletes a collection and returns true', function (): void {
        $mock = new MockClient([
            DeleteCollectionRequest::class => MockResponse::make(
                ['result' => true, 'status' => 'ok', 'time' => 0.01],
            ),
        ]);

        $result = makeClient($mock)->deleteCollection('old');

        expect($result)->toBeTrue();
        $mock->assertSent(DeleteCollectionRequest::class);
    });
});

describe('Qdrant::getCollection', function (): void {
    it('returns collection info', function (): void {
        $mock = new MockClient([
            GetCollectionRequest::class => MockResponse::make([
                'result' => [
                    'status' => 'green',
                    'points_count' => 500,
                    'indexed_vectors_count' => 490,
                    'segments_count' => 2,
                    'config' => [],
                ],
                'status' => 'ok',
            ]),
        ]);

        $info = makeClient($mock)->getCollection('test');

        expect($info)->toBeInstanceOf(CollectionInfo::class)
            ->and($info->status)->toBe('green')
            ->and($info->pointsCount)->toBe(500);
    });
});

describe('Qdrant::getPoints', function (): void {
    it('returns points by ids', function (): void {
        $mock = new MockClient([
            GetPointsRequest::class => MockResponse::make([
                'result' => [
                    ['id' => 'abc', 'payload' => ['title' => 'First'], 'vector' => null],
                    ['id' => 'def', 'payload' => ['title' => 'Second'], 'vector' => null],
                ],
                'status' => 'ok',
            ]),
        ]);

        $results = makeClient($mock)->getPoints('coll', ['abc', 'def']);

        expect($results)->toHaveCount(2)
            ->and($results[0]->id)->toBe('abc')
            ->and($results[0]->payload['title'])->toBe('First')
            ->and($results[1]->id)->toBe('def');
        $mock->assertSent(GetPointsRequest::class);
    });

    it('handles empty result', function (): void {
        $mock = new MockClient([
            GetPointsRequest::class => MockResponse::make([
                'result' => [],
                'status' => 'ok',
            ]),
        ]);

        $results = makeClient($mock)->getPoints('coll', ['nonexistent']);

        expect($results)->toBe([]);
    });
});

describe('Qdrant::setPayload', function (): void {
    it('updates payload and returns result', function (): void {
        $mock = new MockClient([
            SetPayloadRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 10],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->setPayload('coll', ['abc'], ['play_count' => 5]);

        expect($result)->toBeInstanceOf(UpsertResult::class)
            ->and($result->completed())->toBeTrue();
        $mock->assertSent(SetPayloadRequest::class);
    });
});

describe('Qdrant::count', function (): void {
    it('returns count as integer', function (): void {
        $mock = new MockClient([
            CountPointsRequest::class => MockResponse::make([
                'result' => ['count' => 42],
                'status' => 'ok',
            ]),
        ]);

        $count = makeClient($mock)->count('coll');

        expect($count)->toBe(42);
        $mock->assertSent(CountPointsRequest::class);
    });

    it('passes filter through', function (): void {
        $mock = new MockClient([
            CountPointsRequest::class => MockResponse::make([
                'result' => ['count' => 5],
                'status' => 'ok',
            ]),
        ]);

        $filter = ['must' => [['key' => 'type', 'match' => ['value' => 'track']]]];
        $count = makeClient($mock)->count('coll', $filter);

        expect($count)->toBe(5);
    });
});

describe('Qdrant::createPayloadIndex', function (): void {
    it('creates an index and returns result', function (): void {
        $mock = new MockClient([
            CreatePayloadIndexRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 20],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->createPayloadIndex('coll', 'artist', 'keyword');

        expect($result)->toBeInstanceOf(UpsertResult::class)
            ->and($result->completed())->toBeTrue();
        $mock->assertSent(CreatePayloadIndexRequest::class);
    });
});

describe('Qdrant::deletePayloadIndex', function (): void {
    it('deletes an index and returns result', function (): void {
        $mock = new MockClient([
            DeletePayloadIndexRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 21],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->deletePayloadIndex('coll', 'artist');

        expect($result)->toBeInstanceOf(UpsertResult::class)
            ->and($result->completed())->toBeTrue();
        $mock->assertSent(DeletePayloadIndexRequest::class);
    });
});

describe('Qdrant::upsert', function (): void {
    it('upserts raw array points', function (): void {
        $mock = new MockClient([
            UpsertPointsRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 1],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->upsert('coll', [
            ['id' => 'a', 'vector' => [0.1, 0.2], 'payload' => ['key' => 'val']],
        ]);

        expect($result)->toBeInstanceOf(UpsertResult::class)
            ->and($result->completed())->toBeTrue();
        $mock->assertSent(UpsertPointsRequest::class);
    });

    it('upserts Point DTOs', function (): void {
        $mock = new MockClient([
            UpsertPointsRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 2],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->upsert('coll', [
            new Point('b', [0.3, 0.4], ['name' => 'test']),
        ]);

        expect($result->completed())->toBeTrue();
    });
});

describe('Qdrant::search', function (): void {
    it('returns scored points', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [
                    ['id' => 'x', 'score' => 0.95, 'payload' => ['title' => 'Hit']],
                    ['id' => 'y', 'score' => 0.87, 'payload' => ['title' => 'Near']],
                ],
                'status' => 'ok',
            ]),
        ]);

        $results = makeClient($mock)->search('coll', [0.1, 0.2], limit: 2);

        expect($results)->toHaveCount(2)
            ->and($results[0])->toBeInstanceOf(ScoredPoint::class)
            ->and($results[0]->score)->toBe(0.95)
            ->and($results[1]->payload['title'])->toBe('Near');
    });

    it('passes filter to request', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [],
                'status' => 'ok',
            ]),
        ]);

        $filter = ['must' => [['key' => 'type', 'match' => ['value' => 'track']]]];
        makeClient($mock)->search('coll', [0.1], filter: $filter);

        $mock->assertSent(function (SearchPointsRequest $request) use ($filter): bool {
            $body = invade($request)->defaultBody();

            return $body['filter'] === $filter;
        });
    });

    it('handles empty results', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [],
                'status' => 'ok',
            ]),
        ]);

        $results = makeClient($mock)->search('coll', [0.1]);

        expect($results)->toBe([]);
    });
});

describe('Qdrant::scroll', function (): void {
    it('returns scroll result with pagination', function (): void {
        $mock = new MockClient([
            ScrollPointsRequest::class => MockResponse::make([
                'result' => [
                    'points' => [
                        ['id' => 1, 'payload' => ['name' => 'one']],
                    ],
                    'next_page_offset' => 2,
                ],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->scroll('coll', limit: 1);

        expect($result)->toBeInstanceOf(ScrollResult::class)
            ->and($result->points)->toHaveCount(1)
            ->and($result->hasMore())->toBeTrue()
            ->and($result->nextOffset)->toBe(2);
    });
});

describe('Qdrant::delete', function (): void {
    it('deletes by ids', function (): void {
        $mock = new MockClient([
            DeletePointsRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 5],
                'status' => 'ok',
            ]),
        ]);

        $result = makeClient($mock)->delete('coll', ids: [1, 2, 3]);

        expect($result->completed())->toBeTrue();
        $mock->assertSent(DeletePointsRequest::class);
    });

    it('deletes by filter', function (): void {
        $mock = new MockClient([
            DeletePointsRequest::class => MockResponse::make([
                'result' => ['status' => 'completed', 'operation_id' => 6],
                'status' => 'ok',
            ]),
        ]);

        $filter = ['must' => [['key' => 'old', 'match' => ['value' => true]]]];
        $result = makeClient($mock)->delete('coll', filter: $filter);

        expect($result->completed())->toBeTrue();
    });
});

describe('Qdrant::connector', function (): void {
    it('exposes the underlying connector', function (): void {
        $connector = new QdrantConnector('http://localhost:6333');
        $client = new Qdrant($connector);

        expect($client->connector())->toBe($connector);
    });
});
