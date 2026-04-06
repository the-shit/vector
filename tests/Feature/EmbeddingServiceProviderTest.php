<?php

declare(strict_types=1);

use TheShit\Vector\Contracts\EmbeddingClient;
use TheShit\Vector\Embeddings\NullEmbeddings;
use TheShit\Vector\Embeddings\OllamaEmbeddings;
use TheShit\Vector\Embeddings\OpenAiEmbeddings;
use TheShit\Vector\Tests\TestCase;

uses(TestCase::class);

describe('VectorServiceProvider embedding resolution', function (): void {
    it('resolves OllamaEmbeddings by default', function (): void {
        config([
            'vector.embeddings.provider' => 'ollama',
            'vector.embeddings.url' => 'http://localhost:11434',
        ]);
        app()->forgetInstance(EmbeddingClient::class);

        $client = app(EmbeddingClient::class);

        expect($client)->toBeInstanceOf(OllamaEmbeddings::class);
    });

    it('resolves OpenAiEmbeddings when provider is openai', function (): void {
        config([
            'vector.embeddings.provider' => 'openai',
            'vector.embeddings.url' => 'https://api.openai.com',
            'vector.embeddings.api_key' => 'sk-test',
        ]);
        app()->forgetInstance(EmbeddingClient::class);

        $client = app(EmbeddingClient::class);

        expect($client)->toBeInstanceOf(OpenAiEmbeddings::class);
    });

    it('resolves NullEmbeddings when provider is none', function (): void {
        config(['vector.embeddings.provider' => 'none']);
        app()->forgetInstance(EmbeddingClient::class);

        $client = app(EmbeddingClient::class);

        expect($client)->toBeInstanceOf(NullEmbeddings::class);
    });

    it('resolves NullEmbeddings for unknown provider', function (): void {
        config(['vector.embeddings.provider' => 'unknown-provider']);
        app()->forgetInstance(EmbeddingClient::class);

        $client = app(EmbeddingClient::class);

        expect($client)->toBeInstanceOf(NullEmbeddings::class);
    });
});
