<?php

declare(strict_types=1);

use Saloon\Http\Auth\HeaderAuthenticator;
use TheShit\Vector\QdrantConnector;

describe('QdrantConnector', function (): void {
    it('resolves base url', function (): void {
        $connector = new QdrantConnector('http://localhost:6333');

        expect($connector->resolveBaseUrl())->toBe('http://localhost:6333');
    });

    it('trims trailing slash from base url', function (): void {
        $connector = new QdrantConnector('http://localhost:6333/');

        expect($connector->resolveBaseUrl())->toBe('http://localhost:6333');
    });

    it('returns header auth when api key is set', function (): void {
        $connector = new QdrantConnector('http://localhost:6333', 'my-secret-key');
        $auth = invade($connector)->defaultAuth();

        expect($auth)->toBeInstanceOf(HeaderAuthenticator::class);
    });

    it('returns null auth when no api key', function (): void {
        $connector = new QdrantConnector('http://localhost:6333');
        $auth = invade($connector)->defaultAuth();

        expect($auth)->toBeNull();
    });

    it('sets default headers', function (): void {
        $connector = new QdrantConnector('http://localhost:6333');
        $headers = invade($connector)->defaultHeaders();

        expect($headers)->toBe([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    });
});
