<?php

declare(strict_types=1);

use TheShit\Vector\Contracts\VectorClient;
use TheShit\Vector\Qdrant;
use TheShit\Vector\QdrantConnector;
use TheShit\Vector\Tests\TestCase;

uses(TestCase::class);

it('registers QdrantConnector as singleton', function (): void {
    $a = $this->app->make(QdrantConnector::class);
    $b = $this->app->make(QdrantConnector::class);

    expect($a)->toBe($b);
});

it('registers VectorClient interface bound to Qdrant', function (): void {
    $client = $this->app->make(VectorClient::class);

    expect($client)->toBeInstanceOf(Qdrant::class);
});

it('configures connector from config', function (): void {
    $connector = $this->app->make(QdrantConnector::class);

    expect($connector->resolveBaseUrl())->toBe('http://localhost:6333');
});
