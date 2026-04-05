<?php

declare(strict_types=1);

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

describe('GetPointsRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new GetPointsRequest('coll', [1, 2]);

        expect($request->resolveEndpoint())->toBe('/collections/coll/points');
    });

    it('builds body with ids and defaults', function (): void {
        $request = new GetPointsRequest('coll', ['uuid-1', 'uuid-2']);
        $body = invade($request)->defaultBody();

        expect($body)->toBe([
            'ids' => ['uuid-1', 'uuid-2'],
            'with_payload' => true,
            'with_vector' => false,
        ]);
    });

    it('respects withVector option', function (): void {
        $request = new GetPointsRequest('coll', [1], withVector: true);
        $body = invade($request)->defaultBody();

        expect($body['with_vector'])->toBeTrue();
    });
});

describe('SetPayloadRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new SetPayloadRequest('coll', [1], ['key' => 'val']);

        expect($request->resolveEndpoint())->toBe('/collections/coll/points/payload');
    });

    it('builds body with payload and points', function (): void {
        $request = new SetPayloadRequest('coll', ['a', 'b'], ['status' => 'verified']);
        $body = invade($request)->defaultBody();

        expect($body)->toBe([
            'payload' => ['status' => 'verified'],
            'points' => ['a', 'b'],
        ]);
    });

    it('sets wait query param', function (): void {
        $request = new SetPayloadRequest('coll', [1], ['k' => 'v'], wait: false);
        $query = invade($request)->defaultQuery();

        expect($query['wait'])->toBe('false');
    });
});

describe('CountPointsRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new CountPointsRequest('coll');

        expect($request->resolveEndpoint())->toBe('/collections/coll/points/count');
    });

    it('builds body with exact flag', function (): void {
        $request = new CountPointsRequest('coll');
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['exact' => true]);
    });

    it('includes filter when provided', function (): void {
        $filter = ['must' => [['key' => 'type', 'match' => ['value' => 'track']]]];
        $request = new CountPointsRequest('coll', $filter);
        $body = invade($request)->defaultBody();

        expect($body['filter'])->toBe($filter)
            ->and($body['exact'])->toBeTrue();
    });
});

describe('CreatePayloadIndexRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new CreatePayloadIndexRequest('coll', 'artist');

        expect($request->resolveEndpoint())->toBe('/collections/coll/index');
    });

    it('builds body with field name only', function (): void {
        $request = new CreatePayloadIndexRequest('coll', 'artist');
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['field_name' => 'artist']);
    });

    it('includes field schema when provided', function (): void {
        $request = new CreatePayloadIndexRequest('coll', 'artist', 'keyword');
        $body = invade($request)->defaultBody();

        expect($body)->toBe(['field_name' => 'artist', 'field_schema' => 'keyword']);
    });

    it('sets wait query param', function (): void {
        $request = new CreatePayloadIndexRequest('coll', 'artist', wait: false);
        $query = invade($request)->defaultQuery();

        expect($query['wait'])->toBe('false');
    });
});

describe('DeletePayloadIndexRequest', function (): void {
    it('resolves endpoint with field name', function (): void {
        $request = new DeletePayloadIndexRequest('coll', 'artist');

        expect($request->resolveEndpoint())->toBe('/collections/coll/index/artist');
    });

    it('sets wait query param', function (): void {
        $request = new DeletePayloadIndexRequest('coll', 'artist', wait: true);
        $query = invade($request)->defaultQuery();

        expect($query['wait'])->toBe('true');
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
