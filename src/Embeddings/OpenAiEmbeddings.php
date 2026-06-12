<?php

declare(strict_types=1);

namespace TheShit\Vector\Embeddings;

use Saloon\Exceptions\Request\RequestException;
use TheShit\Vector\Contracts\EmbeddingClient;
use TheShit\Vector\Embeddings\Requests\OpenAiEmbedRequest;
use TheShit\Vector\Exceptions\EmbeddingRequestException;

class OpenAiEmbeddings implements EmbeddingClient
{
    public function __construct(
        protected readonly OpenAiConnector $connector,
        protected readonly string $model = 'text-embedding-3-large',
        protected readonly ?int $dimensions = null,
        protected readonly bool $sanitize = false,
    ) {}

    /**
     * @return array<float>
     *
     * @throws EmbeddingRequestException
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
     *
     * @throws EmbeddingRequestException
     */
    public function embedBatch(array $texts): array
    {
        if ($this->sanitize) {
            $texts = array_map(EmbeddingTextSanitizer::sanitize(...), $texts);
        }

        $texts = array_values(array_filter($texts, fn (string $t): bool => trim($t) !== ''));

        if ($texts === []) {
            return [];
        }

        try {
            $response = $this->connector->send(new OpenAiEmbedRequest($this->model, $texts, $this->dimensions));
            $response->throw();
        } catch (RequestException $exception) {
            throw EmbeddingRequestException::fromSaloon($this->model, $exception);
        }

        /** @var array<array{embedding: array<float>}> $data */
        $data = $response->json('data') ?? [];

        return array_map(
            fn (mixed $item): array => is_array($item) && isset($item['embedding']) && is_array($item['embedding'])
                ? array_map(fn (mixed $v): float => $v, $item['embedding'])
                : [],
            $data,
        );
    }
}
