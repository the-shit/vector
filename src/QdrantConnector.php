<?php

declare(strict_types=1);

namespace TheShit\Vector;

use Saloon\Http\Auth\HeaderAuthenticator;
use Saloon\Http\Connector;
use Saloon\Traits\Plugins\HasTimeout;

class QdrantConnector extends Connector
{
    use HasTimeout;

    protected int $connectTimeout = 10;

    protected int $requestTimeout = 30;

    public function __construct(
        protected readonly string $baseUrl,
        protected readonly ?string $apiKey = null,
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

    protected function defaultAuth(): ?HeaderAuthenticator
    {
        if ($this->apiKey === null) {
            return null;
        }

        return new HeaderAuthenticator($this->apiKey, 'api-key');
    }
}
