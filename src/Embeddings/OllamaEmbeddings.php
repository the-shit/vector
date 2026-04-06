<?php

declare(strict_types=1);

namespace TheShit\Vector\Embeddings;

use Saloon\Exceptions\Request\RequestException;
use TheShit\Vector\Contracts\EmbeddingClient;
use TheShit\Vector\Embeddings\Requests\OllamaEmbedRequest;

class OllamaEmbeddings implements EmbeddingClient
{
    public function __construct(
        protected readonly OllamaConnector $connector,
        protected readonly string $model = 'bge-large',
    ) {}

    /**
     * @return array<float>
     */
    public function embed(string $text): array
    {
        if (trim($text) === '') {
            return [];
        }

        $result = $this->embedBatch([$text]);

        return $result[0] ?? [];
    }

    /**
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts): array
    {
        $texts = array_values(array_filter($texts, fn (string $t): bool => trim($t) !== ''));

        if ($texts === []) {
            return [];
        }

        try {
            $response = $this->connector->send(new OllamaEmbedRequest($this->model, $texts));
            $response->throw();
        } catch (RequestException) {
            return array_fill(0, count($texts), []);
        }

        /** @var array<array<float>> $embeddings */
        $embeddings = $response->json('embeddings') ?? [];

        return array_map(
            fn (mixed $embedding): array => is_array($embedding)
                ? array_map(fn (mixed $v): float => (float) $v, $embedding)
                : [],
            $embeddings,
        );
    }
}
