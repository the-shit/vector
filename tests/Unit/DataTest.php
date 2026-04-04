<?php

declare(strict_types=1);

use TheShit\Vector\Data\CollectionInfo;
use TheShit\Vector\Data\Point;
use TheShit\Vector\Data\ScoredPoint;
use TheShit\Vector\Data\ScrollResult;
use TheShit\Vector\Data\UpsertResult;

describe('Point', function (): void {
    it('creates from constructor', function (): void {
        $point = new Point(id: 'abc-123', vector: [0.1, 0.2, 0.3], payload: ['key' => 'value']);

        expect($point->id)->toBe('abc-123')
            ->and($point->vector)->toBe([0.1, 0.2, 0.3])
            ->and($point->payload)->toBe(['key' => 'value']);
    });

    it('converts to array', function (): void {
        $point = new Point(id: 42, vector: [0.5], payload: ['name' => 'test']);

        expect($point->toArray())->toBe([
            'id' => 42,
            'vector' => [0.5],
            'payload' => ['name' => 'test'],
        ]);
    });

    it('defaults payload to empty array', function (): void {
        $point = new Point(id: 1, vector: [0.1]);

        expect($point->payload)->toBe([]);
    });
});

describe('ScoredPoint', function (): void {
    it('creates from array', function (): void {
        $point = ScoredPoint::fromArray([
            'id' => 'uuid-1',
            'score' => 0.95,
            'payload' => ['title' => 'Test'],
            'vector' => [0.1, 0.2],
            'version' => 3,
        ]);

        expect($point->id)->toBe('uuid-1')
            ->and($point->score)->toBe(0.95)
            ->and($point->payload)->toBe(['title' => 'Test'])
            ->and($point->vector)->toBe([0.1, 0.2])
            ->and($point->version)->toBe(3);
    });

    it('handles missing optional fields', function (): void {
        $point = ScoredPoint::fromArray(['id' => 1, 'score' => 0.5]);

        expect($point->payload)->toBe([])
            ->and($point->vector)->toBeNull()
            ->and($point->version)->toBeNull();
    });

    it('normalizes JSON-encoded arrays in payload', function (): void {
        $point = ScoredPoint::fromArray([
            'id' => 1,
            'score' => 0.9,
            'payload' => ['tags' => '["rock","punk"]', 'title' => 'Test'],
        ]);

        expect($point->payload['tags'])->toBe(['rock', 'punk'])
            ->and($point->payload['title'])->toBe('Test');
    });

    it('leaves normal arrays untouched', function (): void {
        $point = ScoredPoint::fromArray([
            'id' => 1,
            'score' => 0.9,
            'payload' => ['tags' => ['rock', 'punk']],
        ]);

        expect($point->payload['tags'])->toBe(['rock', 'punk']);
    });

    it('does not decode non-array JSON strings', function (): void {
        $point = ScoredPoint::fromArray([
            'id' => 1,
            'score' => 0.9,
            'payload' => ['name' => 'just a string', 'bracket' => '[not json'],
        ]);

        expect($point->payload['name'])->toBe('just a string')
            ->and($point->payload['bracket'])->toBe('[not json');
    });
});

describe('CollectionInfo', function (): void {
    it('creates from array', function (): void {
        $info = CollectionInfo::fromArray([
            'status' => 'green',
            'points_count' => 1000,
            'indexed_vectors_count' => 950,
            'segments_count' => 3,
            'config' => ['params' => ['vectors' => ['size' => 1536]]],
        ]);

        expect($info->status)->toBe('green')
            ->and($info->pointsCount)->toBe(1000)
            ->and($info->indexedVectorsCount)->toBe(950)
            ->and($info->segmentsCount)->toBe(3)
            ->and($info->config)->toHaveKey('params');
    });

    it('handles missing fields with defaults', function (): void {
        $info = CollectionInfo::fromArray([]);

        expect($info->status)->toBe('unknown')
            ->and($info->pointsCount)->toBe(0)
            ->and($info->indexedVectorsCount)->toBe(0)
            ->and($info->segmentsCount)->toBe(0)
            ->and($info->config)->toBe([]);
    });
});

describe('UpsertResult', function (): void {
    it('creates from array', function (): void {
        $result = UpsertResult::fromArray(['status' => 'completed', 'operation_id' => 42]);

        expect($result->status)->toBe('completed')
            ->and($result->operationId)->toBe(42)
            ->and($result->completed())->toBeTrue();
    });

    it('reports incomplete status', function (): void {
        $result = UpsertResult::fromArray(['status' => 'acknowledged']);

        expect($result->completed())->toBeFalse();
    });

    it('handles missing fields', function (): void {
        $result = UpsertResult::fromArray([]);

        expect($result->status)->toBe('unknown')
            ->and($result->operationId)->toBeNull();
    });
});

describe('ScrollResult', function (): void {
    it('creates from array with points', function (): void {
        $result = ScrollResult::fromArray([
            'points' => [
                ['id' => 1, 'payload' => ['name' => 'one']],
                ['id' => 2, 'payload' => ['name' => 'two']],
            ],
            'next_page_offset' => 3,
        ]);

        expect($result->points)->toHaveCount(2)
            ->and($result->points[0]->id)->toBe(1)
            ->and($result->points[1]->payload)->toBe(['name' => 'two'])
            ->and($result->nextOffset)->toBe(3)
            ->and($result->hasMore())->toBeTrue();
    });

    it('handles last page', function (): void {
        $result = ScrollResult::fromArray([
            'points' => [],
            'next_page_offset' => null,
        ]);

        expect($result->points)->toBe([])
            ->and($result->hasMore())->toBeFalse();
    });

    it('handles missing fields', function (): void {
        $result = ScrollResult::fromArray([]);

        expect($result->points)->toBe([])
            ->and($result->nextOffset)->toBeNull();
    });

    it('normalizes JSON-encoded arrays in scroll payloads', function (): void {
        $result = ScrollResult::fromArray([
            'points' => [
                ['id' => 1, 'payload' => ['genres' => '["electronic","ambient"]']],
            ],
        ]);

        expect($result->points[0]->payload['genres'])->toBe(['electronic', 'ambient']);
    });
});
