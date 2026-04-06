<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TheShit\Vector\Qdrant;
use TheShit\Vector\QdrantConnector;
use TheShit\Vector\QueryBuilder;
use TheShit\Vector\Requests\Points\SearchPointsRequest;
use TheShit\Vector\Tests\TestCase;

uses(TestCase::class);

function makeQdrant(MockClient $mock): Qdrant
{
    $connector = new QdrantConnector('http://localhost:6333', 'test-key');
    $connector->withMockClient($mock);

    return new Qdrant($connector);
}

describe('Qdrant::query', function (): void {
    it('returns a QueryBuilder instance', function (): void {
        $client = makeQdrant(new MockClient([]));

        expect($client->query('memories'))->toBeInstanceOf(QueryBuilder::class);
    });

    it('executes search through the builder', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [
                    ['id' => 'x', 'score' => 0.9, 'payload' => ['project' => 'lexi']],
                ],
                'status' => 'ok',
            ]),
        ]);

        $results = makeQdrant($mock)
            ->query('memories')
            ->where('project', 'lexi')
            ->nearVector([0.1, 0.2])
            ->limit(5)
            ->search();

        expect($results)->toHaveCount(1)
            ->and($results[0]->id)->toBe('x')
            ->and($results[0]->score)->toBe(0.9);

        $mock->assertSent(function (SearchPointsRequest $request): bool {
            $body = invade($request)->defaultBody();

            return $body['limit'] === 5
                && $body['filter'] === ['must' => [['key' => 'project', 'match' => ['value' => 'lexi']]]]
                && ! isset($body['score_threshold']);
        });
    });

    it('passes score_threshold through minScore', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [],
                'status' => 'ok',
            ]),
        ]);

        makeQdrant($mock)
            ->query('memories')
            ->nearVector([0.1])
            ->minScore(0.75)
            ->search();

        $mock->assertSent(function (SearchPointsRequest $request): bool {
            $body = invade($request)->defaultBody();

            return $body['score_threshold'] === 0.75;
        });
    });
});

describe('Qdrant::collection', function (): void {
    it('returns a QueryBuilder via static call', function (): void {
        $builder = Qdrant::collection('memories');

        expect($builder)->toBeInstanceOf(QueryBuilder::class);
    });

    it('resolves VectorClient from the container', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [
                    ['id' => 'm-1', 'score' => 0.88, 'payload' => []],
                ],
                'status' => 'ok',
            ]),
        ]);

        $connector = $this->app->make(QdrantConnector::class);
        $connector->withMockClient($mock);

        $results = Qdrant::collection('memories')
            ->nearVector([0.5])
            ->search();

        expect($results)->toHaveCount(1)
            ->and($results[0]->id)->toBe('m-1');
    });
});

describe('Qdrant::search with scoreThreshold', function (): void {
    it('passes score_threshold to request', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [],
                'status' => 'ok',
            ]),
        ]);

        makeQdrant($mock)->search('coll', [0.1], scoreThreshold: 0.5);

        $mock->assertSent(function (SearchPointsRequest $request): bool {
            $body = invade($request)->defaultBody();

            return $body['score_threshold'] === 0.5;
        });
    });

    it('omits score_threshold when null', function (): void {
        $mock = new MockClient([
            SearchPointsRequest::class => MockResponse::make([
                'result' => [],
                'status' => 'ok',
            ]),
        ]);

        makeQdrant($mock)->search('coll', [0.1]);

        $mock->assertSent(function (SearchPointsRequest $request): bool {
            $body = invade($request)->defaultBody();

            return ! isset($body['score_threshold']);
        });
    });
});
