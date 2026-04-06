<?php

declare(strict_types=1);

use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TheShit\Vector\Qdrant;
use TheShit\Vector\QdrantConnector;
use TheShit\Vector\Requests\Collections\ListCollectionsRequest;
use TheShit\Vector\Tests\TestCase;

uses(TestCase::class);

function makeListQdrant(MockClient $mock): Qdrant
{
    $connector = new QdrantConnector('http://localhost:6333', 'test-key');
    $connector->withMockClient($mock);

    return new Qdrant($connector);
}

describe('Qdrant::listCollections', function (): void {
    it('returns collection names', function (): void {
        $mock = new MockClient([
            ListCollectionsRequest::class => MockResponse::make([
                'result' => [
                    'collections' => [
                        ['name' => 'knowledge_default'],
                        ['name' => 'code'],
                    ],
                ],
            ]),
        ]);

        $result = makeListQdrant($mock)->listCollections();

        expect($result)->toBe(['knowledge_default', 'code']);
    });

    it('returns empty array when no collections', function (): void {
        $mock = new MockClient([
            ListCollectionsRequest::class => MockResponse::make([
                'result' => [
                    'collections' => [],
                ],
            ]),
        ]);

        $result = makeListQdrant($mock)->listCollections();

        expect($result)->toBe([]);
    });

    it('handles null collections gracefully', function (): void {
        $mock = new MockClient([
            ListCollectionsRequest::class => MockResponse::make([
                'result' => [],
            ]),
        ]);

        $result = makeListQdrant($mock)->listCollections();

        expect($result)->toBe([]);
    });
});
