<?php

declare(strict_types=1);

use TheShit\Vector\Contracts\FilterBuilder;
use TheShit\Vector\Contracts\VectorClient;
use TheShit\Vector\Data\ScoredPoint;
use TheShit\Vector\QueryBuilder;

function mockClient(): VectorClient
{
    return Mockery::mock(VectorClient::class);
}

describe('QueryBuilder', function (): void {
    it('searches with vector and default limit', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with('memories', [0.1, 0.2], 10, null, null)
            ->andReturn([
                new ScoredPoint('a', 0.95, ['title' => 'Hit']),
            ]);

        $results = (new QueryBuilder($client, 'memories'))
            ->nearVector([0.1, 0.2])
            ->search();

        expect($results)->toHaveCount(1)
            ->and($results[0]->score)->toBe(0.95);
    });

    it('chains where, whereIn, nearVector, limit, minScore', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with(
                'memories',
                [0.1, 0.2, 0.3],
                10,
                Mockery::on(fn (array $filter): bool => isset($filter['must']) && count($filter['must']) === 2),
                0.75,
            )
            ->andReturn([]);

        $results = (new QueryBuilder($client, 'memories'))
            ->where('project', 'lexi-agent')
            ->whereIn('tags', ['verified'])
            ->nearVector([0.1, 0.2, 0.3])
            ->limit(10)
            ->minScore(0.75)
            ->search();

        expect($results)->toBe([]);
    });

    it('applies whereNot filter', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with(
                'coll',
                [0.5],
                10,
                Mockery::on(fn (array $filter): bool => isset($filter['must_not'])
                    && $filter['must_not'][0]['key'] === 'status'
                    && $filter['must_not'][0]['match']['value'] === 'deleted'),
                null,
            )
            ->andReturn([]);

        (new QueryBuilder($client, 'coll'))
            ->whereNot('status', 'deleted')
            ->nearVector([0.5])
            ->search();
    });

    it('applies whereRange filter', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with(
                'coll',
                [0.5],
                10,
                Mockery::on(fn (array $filter): bool => isset($filter['must'])
                    && $filter['must'][0]['key'] === 'energy'
                    && $filter['must'][0]['range'] === ['gte' => 0.5, 'lte' => 1.0]),
                null,
            )
            ->andReturn([]);

        (new QueryBuilder($client, 'coll'))
            ->whereRange('energy', gte: 0.5, lte: 1.0)
            ->nearVector([0.5])
            ->search();
    });

    it('passes custom limit', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with('coll', [0.1], 5, null, null)
            ->andReturn([]);

        (new QueryBuilder($client, 'coll'))
            ->nearVector([0.1])
            ->limit(5)
            ->search();
    });

    it('passes score threshold', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with('coll', [0.1], 10, null, 0.8)
            ->andReturn([]);

        (new QueryBuilder($client, 'coll'))
            ->nearVector([0.1])
            ->minScore(0.8)
            ->search();
    });

    it('omits filter when no conditions are set', function (): void {
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with('coll', [0.1], 10, null, null)
            ->andReturn([]);

        (new QueryBuilder($client, 'coll'))
            ->nearVector([0.1])
            ->search();
    });

    it('throws when searching without a vector', function (): void {
        $client = mockClient();

        expect(fn (): array => (new QueryBuilder($client, 'coll'))->search())
            ->toThrow(InvalidArgumentException::class, 'A vector is required for search');
    });

    it('delegates count to client', function (): void {
        $client = mockClient();
        $client->shouldReceive('count')
            ->once()
            ->with('memories', null)
            ->andReturn(42);

        $count = (new QueryBuilder($client, 'memories'))
            ->count();

        expect($count)->toBe(42);
    });

    it('passes filter to count', function (): void {
        $client = mockClient();
        $client->shouldReceive('count')
            ->once()
            ->with(
                'memories',
                Mockery::on(fn (array $filter): bool => isset($filter['must'])
                    && $filter['must'][0]['key'] === 'project'
                    && $filter['must'][0]['match']['value'] === 'lexi'),
            )
            ->andReturn(5);

        $count = (new QueryBuilder($client, 'memories'))
            ->where('project', 'lexi')
            ->count();

        expect($count)->toBe(5);
    });

    it('exposes the underlying filter builder', function (): void {
        $client = mockClient();
        $builder = new QueryBuilder($client, 'coll');

        expect($builder->filter())->toBeInstanceOf(FilterBuilder::class);
    });

    it('supports the full fluent chain from the issue', function (): void {
        $embedding = array_fill(0, 1536, 0.01);
        $client = mockClient();
        $client->shouldReceive('search')
            ->once()
            ->with(
                'memories',
                $embedding,
                10,
                Mockery::on(fn (array $filter): bool => count($filter['must']) === 2
                    && $filter['must'][0]['key'] === 'project'
                    && $filter['must'][0]['match']['value'] === 'lexi-agent'
                    && $filter['must'][1]['key'] === 'tags'
                    && $filter['must'][1]['match']['any'] === ['verified']),
                0.75,
            )
            ->andReturn([
                new ScoredPoint('mem-1', 0.92, ['project' => 'lexi-agent']),
            ]);

        $results = (new QueryBuilder($client, 'memories'))
            ->where('project', 'lexi-agent')
            ->whereIn('tags', ['verified'])
            ->nearVector($embedding)
            ->limit(10)
            ->minScore(0.75)
            ->search();

        expect($results)->toHaveCount(1)
            ->and($results[0]->id)->toBe('mem-1');
    });
});
