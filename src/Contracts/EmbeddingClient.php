<?php

declare(strict_types=1);

namespace TheShit\Vector\Contracts;

interface EmbeddingClient
{
    /**
     * Generate an embedding vector for the given text.
     *
     * @return array<float>
     */
    public function embed(string $text): array;

    /**
     * Generate embedding vectors for multiple texts.
     *
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts): array;
}
