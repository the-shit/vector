<?php

declare(strict_types=1);

use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TheShit\Vector\Contracts\EmbeddingClient;
use TheShit\Vector\Embeddings\NullEmbeddings;
use TheShit\Vector\Embeddings\OllamaConnector;
use TheShit\Vector\Embeddings\OllamaEmbeddings;
use TheShit\Vector\Embeddings\OpenAiConnector;
use TheShit\Vector\Embeddings\OpenAiEmbeddings;
use TheShit\Vector\Embeddings\Requests\OllamaEmbedRequest;
use TheShit\Vector\Embeddings\Requests\OpenAiEmbedRequest;

describe('OllamaConnector', function (): void {
    it('resolves base url', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');

        expect($connector->resolveBaseUrl())->toBe('http://localhost:11434');
    });

    it('trims trailing slash', function (): void {
        $connector = new OllamaConnector('http://localhost:11434/');

        expect($connector->resolveBaseUrl())->toBe('http://localhost:11434');
    });
});

describe('OpenAiConnector', function (): void {
    it('resolves base url', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');

        expect($connector->resolveBaseUrl())->toBe('https://api.openai.com');
    });

    it('sets bearer auth from api key', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test-key');
        $auth = invade($connector)->defaultAuth();

        expect($auth)->toBeInstanceOf(TokenAuthenticator::class);
    });
});

describe('OllamaEmbedRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new OllamaEmbedRequest('bge-large', ['hello']);

        expect($request->resolveEndpoint())->toBe('/api/embed');
    });

    it('includes model and input in body', function (): void {
        $request = new OllamaEmbedRequest('bge-large', ['hello', 'world']);
        $body = invade($request)->defaultBody();

        expect($body)->toBe([
            'model' => 'bge-large',
            'input' => ['hello', 'world'],
        ]);
    });
});

describe('OpenAiEmbedRequest', function (): void {
    it('resolves endpoint', function (): void {
        $request = new OpenAiEmbedRequest('text-embedding-3-large', ['hello']);

        expect($request->resolveEndpoint())->toBe('/v1/embeddings');
    });

    it('includes model and input in body', function (): void {
        $request = new OpenAiEmbedRequest('text-embedding-3-large', ['hello']);
        $body = invade($request)->defaultBody();

        expect($body)->toBe([
            'model' => 'text-embedding-3-large',
            'input' => ['hello'],
        ]);
    });

    it('includes dimensions when specified', function (): void {
        $request = new OpenAiEmbedRequest('text-embedding-3-large', ['hello'], 1024);
        $body = invade($request)->defaultBody();

        expect($body)->toBe([
            'model' => 'text-embedding-3-large',
            'input' => ['hello'],
            'dimensions' => 1024,
        ]);
    });

    it('omits dimensions when null', function (): void {
        $request = new OpenAiEmbedRequest('text-embedding-3-large', ['hello']);
        $body = invade($request)->defaultBody();

        expect($body)->not->toHaveKey('dimensions');
    });
});

describe('OllamaEmbeddings', function (): void {
    it('embeds single text', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $connector->withMockClient(new MockClient([
            OllamaEmbedRequest::class => MockResponse::make([
                'embeddings' => [[0.1, 0.2, 0.3]],
            ]),
        ]));

        $client = new OllamaEmbeddings($connector, 'bge-large');
        $result = $client->embed('hello world');

        expect($result)->toBe([0.1, 0.2, 0.3]);
    });

    it('returns empty array for empty text', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $client = new OllamaEmbeddings($connector);

        expect($client->embed(''))->toBe([])
            ->and($client->embed('   '))->toBe([]);
    });

    it('embeds batch of texts', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $connector->withMockClient(new MockClient([
            OllamaEmbedRequest::class => MockResponse::make([
                'embeddings' => [[0.1, 0.2], [0.3, 0.4]],
            ]),
        ]));

        $client = new OllamaEmbeddings($connector, 'bge-large');
        $result = $client->embedBatch(['hello', 'world']);

        expect($result)->toBe([[0.1, 0.2], [0.3, 0.4]]);
    });

    it('filters empty strings from batch', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $connector->withMockClient(new MockClient([
            OllamaEmbedRequest::class => MockResponse::make([
                'embeddings' => [[0.1, 0.2]],
            ]),
        ]));

        $client = new OllamaEmbeddings($connector, 'bge-large');
        $result = $client->embedBatch(['hello', '', '  ']);

        expect($result)->toBe([[0.1, 0.2]]);
    });

    it('returns empty arrays on batch with only empty strings', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $client = new OllamaEmbeddings($connector);

        expect($client->embedBatch(['', '  ']))->toBe([]);
    });

    it('returns empty arrays on request failure', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $connector->withMockClient(new MockClient([
            OllamaEmbedRequest::class => MockResponse::make([], 500),
        ]));

        $client = new OllamaEmbeddings($connector, 'bge-large');
        $result = $client->embed('hello');

        expect($result)->toBe([]);
    });

    it('handles malformed embedding response', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $connector->withMockClient(new MockClient([
            OllamaEmbedRequest::class => MockResponse::make([
                'embeddings' => ['not-an-array'],
            ]),
        ]));

        $client = new OllamaEmbeddings($connector, 'bge-large');
        $result = $client->embed('hello');

        expect($result)->toBe([]);
    });

    it('handles missing embeddings key', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $connector->withMockClient(new MockClient([
            OllamaEmbedRequest::class => MockResponse::make(['model' => 'bge-large']),
        ]));

        $client = new OllamaEmbeddings($connector, 'bge-large');
        $result = $client->embed('hello');

        expect($result)->toBe([]);
    });

    it('implements EmbeddingClient contract', function (): void {
        $connector = new OllamaConnector('http://localhost:11434');
        $client = new OllamaEmbeddings($connector);

        expect($client)->toBeInstanceOf(EmbeddingClient::class);
    });
});

describe('OpenAiEmbeddings', function (): void {
    it('embeds single text', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make([
                'data' => [['embedding' => [0.5, 0.6, 0.7]]],
            ]),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large');
        $result = $client->embed('hello world');

        expect($result)->toBe([0.5, 0.6, 0.7]);
    });

    it('returns empty array for empty text', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $client = new OpenAiEmbeddings($connector);

        expect($client->embed(''))->toBe([])
            ->and($client->embed('   '))->toBe([]);
    });

    it('embeds batch of texts', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make([
                'data' => [
                    ['embedding' => [0.1, 0.2]],
                    ['embedding' => [0.3, 0.4]],
                ],
            ]),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large');
        $result = $client->embedBatch(['hello', 'world']);

        expect($result)->toBe([[0.1, 0.2], [0.3, 0.4]]);
    });

    it('filters empty strings from batch', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make([
                'data' => [['embedding' => [0.1, 0.2]]],
            ]),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large');
        $result = $client->embedBatch(['hello', '', '  ']);

        expect($result)->toBe([[0.1, 0.2]]);
    });

    it('returns empty on batch with only empty strings', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $client = new OpenAiEmbeddings($connector);

        expect($client->embedBatch(['', '  ']))->toBe([]);
    });

    it('returns empty arrays on request failure', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make([], 500),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large');
        $result = $client->embed('hello');

        expect($result)->toBe([]);
    });

    it('handles malformed data response', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make([
                'data' => [['no_embedding_key' => true]],
            ]),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large');
        $result = $client->embed('hello');

        expect($result)->toBe([]);
    });

    it('handles missing data key', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make(['model' => 'text-embedding-3-large']),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large');
        $result = $client->embed('hello');

        expect($result)->toBe([]);
    });

    it('passes dimensions to request', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $connector->withMockClient(new MockClient([
            OpenAiEmbedRequest::class => MockResponse::make([
                'data' => [['embedding' => [0.1]]],
            ]),
        ]));

        $client = new OpenAiEmbeddings($connector, 'text-embedding-3-large', 1024);
        $result = $client->embed('hello');

        expect($result)->toBe([0.1]);
    });

    it('implements EmbeddingClient contract', function (): void {
        $connector = new OpenAiConnector('https://api.openai.com', 'sk-test');
        $client = new OpenAiEmbeddings($connector);

        expect($client)->toBeInstanceOf(EmbeddingClient::class);
    });
});

describe('NullEmbeddings', function (): void {
    it('returns empty array for embed', function (): void {
        $client = new NullEmbeddings;

        expect($client->embed('hello'))->toBe([]);
    });

    it('returns empty array for embedBatch', function (): void {
        $client = new NullEmbeddings;

        expect($client->embedBatch(['hello', 'world']))->toBe([]);
    });

    it('implements EmbeddingClient contract', function (): void {
        expect(new NullEmbeddings)->toBeInstanceOf(EmbeddingClient::class);
    });
});
