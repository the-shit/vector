<?php

declare(strict_types=1);

use TheShit\Vector\Filters\QdrantFilter;

describe('QdrantFilter', function (): void {
    it('builds must condition', function (): void {
        $filter = QdrantFilter::where('category', 'music')->toArray();

        expect($filter)->toBe([
            'must' => [['key' => 'category', 'match' => ['value' => 'music']]],
        ]);
    });

    it('chains multiple must conditions', function (): void {
        $filter = QdrantFilter::where('type', 'track')
            ->must('status', 'active')
            ->toArray();

        expect($filter['must'])->toHaveCount(2);
    });

    it('builds must_not condition', function (): void {
        $filter = (new QdrantFilter)
            ->mustNot('status', 'archived')
            ->toArray();

        expect($filter)->toBe([
            'must_not' => [['key' => 'status', 'match' => ['value' => 'archived']]],
        ]);
    });

    it('builds should condition', function (): void {
        $filter = (new QdrantFilter)
            ->should('tag', 'rock')
            ->should('tag', 'jazz')
            ->toArray();

        expect($filter['should'])->toHaveCount(2);
    });

    it('builds mustAny condition', function (): void {
        $filter = (new QdrantFilter)
            ->mustAny('genre', ['rock', 'punk', 'metal'])
            ->toArray();

        expect($filter['must'][0])->toBe([
            'key' => 'genre',
            'match' => ['any' => ['rock', 'punk', 'metal']],
        ]);
    });

    it('builds range condition', function (): void {
        $filter = (new QdrantFilter)
            ->mustRange('energy', gte: 0.5, lte: 1.0)
            ->toArray();

        expect($filter['must'][0])->toBe([
            'key' => 'energy',
            'range' => ['gte' => 0.5, 'lte' => 1.0],
        ]);
    });

    it('omits null range values', function (): void {
        $filter = (new QdrantFilter)
            ->mustRange('tempo', gt: 120.0)
            ->toArray();

        expect($filter['must'][0]['range'])->toBe(['gt' => 120.0]);
    });

    it('builds full text condition', function (): void {
        $filter = (new QdrantFilter)
            ->fullText('description', 'punk rock')
            ->toArray();

        expect($filter['must'][0])->toBe([
            'key' => 'description',
            'match' => ['text' => 'punk rock'],
        ]);
    });

    it('combines must, must_not, and should', function (): void {
        $filter = QdrantFilter::where('type', 'track')
            ->mustNot('status', 'deleted')
            ->should('mood', 'hype')
            ->toArray();

        expect($filter)
            ->toHaveKey('must')
            ->toHaveKey('must_not')
            ->toHaveKey('should');
    });

    it('omits empty condition arrays', function (): void {
        $filter = (new QdrantFilter)->toArray();

        expect($filter)->toBe([]);
    });
});
