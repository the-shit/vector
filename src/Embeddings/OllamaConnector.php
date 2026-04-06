<?php

declare(strict_types=1);

namespace TheShit\Vector\Embeddings;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class OllamaConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 5;

    protected int $requestTimeout = 30;

    public function __construct(
        protected readonly string $baseUrl,
    ) {}

    public function resolveBaseUrl(): string
    {
        return rtrim($this->baseUrl, '/');
    }

    /**
     * @return array<string, string>
     */
    protected function defaultHeaders(): array
    {
        return [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }
}
