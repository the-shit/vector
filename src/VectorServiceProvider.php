<?php

declare(strict_types=1);

namespace TheShit\Vector;

use Illuminate\Support\ServiceProvider;
use TheShit\Vector\Contracts\VectorClient;

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
