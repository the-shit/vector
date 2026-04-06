<?php

declare(strict_types=1);

namespace TheShit\Vector;

use Illuminate\Support\ServiceProvider;
use TheShit\Vector\Contracts\EmbeddingClient;
use TheShit\Vector\Contracts\VectorClient;
use TheShit\Vector\Embeddings\NullEmbeddings;
use TheShit\Vector\Embeddings\OllamaConnector;
use TheShit\Vector\Embeddings\OllamaEmbeddings;
use TheShit\Vector\Embeddings\OpenAiConnector;
use TheShit\Vector\Embeddings\OpenAiEmbeddings;

class VectorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/vector.php', 'vector');

        $this->app->singleton(QdrantConnector::class, function (): QdrantConnector {
            return new QdrantConnector(
                baseUrl: config('vector.url', 'http://localhost:6333'),
                apiKey: config('vector.api_key'),
            );
        });

        $this->app->singleton(Qdrant::class, function ($app): Qdrant {
            return new Qdrant($app->make(QdrantConnector::class));
        });

        $this->app->alias(Qdrant::class, VectorClient::class);

        $this->app->singleton(EmbeddingClient::class, function (): EmbeddingClient {
            $provider = config('vector.embeddings.provider', 'ollama');
            $model = config('vector.embeddings.model', 'bge-large');

            return match ($provider) {
                'ollama' => new OllamaEmbeddings(
                    new OllamaConnector(
                        config('vector.embeddings.url', 'http://localhost:11434'),
                    ),
                    $model,
                ),
                'openai' => new OpenAiEmbeddings(
                    new OpenAiConnector(
                        config('vector.embeddings.url', 'https://api.openai.com'),
                        config('vector.embeddings.api_key', ''),
                    ),
                    $model,
                    config('vector.embeddings.dimensions') ? (int) config('vector.embeddings.dimensions') : null,
                ),
                default => new NullEmbeddings,
            };
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/vector.php' => config_path('vector.php'),
            ], 'vector-config');
        }
    }
}
