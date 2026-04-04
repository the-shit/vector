<?php

declare(strict_types=1);

use TheShit\Vector\Requests\Collections\CreateCollectionRequest;
use TheShit\Vector\Requests\Collections\DeleteCollectionRequest;
use TheShit\Vector\Requests\Collections\GetCollectionRequest;
use TheShit\Vector\Requests\Points\DeletePointsRequest;
use TheShit\Vector\Requests\Points\ScrollPointsRequest;
use TheShit\Vector\Requests\Points\SearchPointsRequest;
use TheShit\Vector\Requests\Points\UpsertPointsRequest;

describe('CreateCollectionRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new CreateCollectionRequest('my_collection');

        expect($request->resolveEndpoint())->toBe('/collections/my_collection');
    });

    it('builds default vector config body', function (): void {
        $request = new CreateCollectionRequest('test', size: 768, distance: 'Euclid');
        $body = invade($request)->defaultBody();

        expect($body)->toBe([
            'vectors' => ['size' => 768, 'distance' => 'Euclid'],
        ]);
    });

    it('supports named vectors', function (): void {
        $named = ['text' => ['size' => 1536, 'distance' => 'Cosine']];
        $request = new CreateCollectionRequest('test', namedVectors: $named);
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['vectors' => $named]);
    });
});

describe('DeleteCollectionRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new DeleteCollectionRequest('old_collection');

        expect($request->resolveEndpoint())->toBe('/collections/old_collection');
    });
});

describe('GetCollectionRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new GetCollectionRequest('info_collection');

        expect($request->resolveEndpoint())->toBe('/collections/info_collection');
    });
});

describe('UpsertPointsRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new UpsertPointsRequest('coll', []);

        expect($request->resolveEndpoint())->toBe('/collections/coll/points');
    });

    it('includes points in body', function (): void {
        $points = [['id' => 1, 'vector' => [0.1], 'payload' => ['k' => 'v']]];
        $request = new UpsertPointsRequest('coll', $points);
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['points' => $points]);
    });

    it('sets wait query param', function (): void {
        $request = new UpsertPointsRequest('coll', [], wait: false);
        $query = invade($request)->defaultQuery();

        expect($query['wait'])->toBe('false');
    });
});

describe('SearchPointsRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new SearchPointsRequest('coll', [0.1]);

        expect($request->resolveEndpoint())->toBe('/collections/coll/points/search');
    });

    it('builds body with vector and limit', function (): void {
        $request = new SearchPointsRequest('coll', [0.1, 0.2], limit: 5);
        $body = invade($request)->defaultBody();

        expect($body['vector'])->toBe([0.1, 0.2])
            ->and($body['limit'])->toBe(5)
            ->and($body['with_payload'])->toBeTrue()
            ->and($body['with_vector'])->toBeFalse();
    });

    it('includes filter when provided', function (): void {
        $filter = ['must' => [['key' => 'type', 'match' => ['value' => 'track']]]];
        $request = new SearchPointsRequest('coll', [0.1], filter: $filter);
        $body = invade($request)->defaultBody();

        expect($body['filter'])->toBe($filter);
    });

    it('omits filter when null', function (): void {
        $request = new SearchPointsRequest('coll', [0.1]);
        $body = invade($request)->defaultBody();

        expect($body)->not->toHaveKey('filter');
    });
});

describe('ScrollPointsRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new ScrollPointsRequest('coll');

        expect($request->resolveEndpoint())->toBe('/collections/coll/points/scroll');
    });

    it('builds body with defaults', function (): void {
        $request = new ScrollPointsRequest('coll');
        $body = invade($request)->defaultBody();

        expect($body['limit'])->toBe(100)
            ->and($body['with_payload'])->toBeTrue()
            ->and($body['with_vector'])->toBeFalse();
    });

    it('includes offset when provided', function (): void {
        $request = new ScrollPointsRequest('coll', offset: 42);
        $body = invade($request)->defaultBody();

        expect($body['offset'])->toBe(42);
    });

    it('omits null offset and filter', function (): void {
        $request = new ScrollPointsRequest('coll');
        $body = invade($request)->defaultBody();

        expect($body)->not->toHaveKey('offset')
            ->and($body)->not->toHaveKey('filter');
    });
});

describe('DeletePointsRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new DeletePointsRequest('coll');

        expect($request->resolveEndpoint())->toBe('/collections/coll/points/delete');
    });

    it('builds body with ids', function (): void {
        $request = new DeletePointsRequest('coll', ids: [1, 2, 'uuid-3']);
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['points' => [1, 2, 'uuid-3']]);
    });

    it('builds body with filter when no ids', function (): void {
        $filter = ['must' => [['key' => 'status', 'match' => ['value' => 'old']]]];
        $request = new DeletePointsRequest('coll', filter: $filter);
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['filter' => $filter]);
    });

    it('sets wait query param', function (): void {
        $request = new DeletePointsRequest('coll', ids: [1], wait: true);
        $query = invade($request)->defaultQuery();

        expect($query['wait'])->toBe('true');
    });
});
