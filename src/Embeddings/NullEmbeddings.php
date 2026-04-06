<?php

declare(strict_types=1);

namespace TheShit\Vector\Embeddings;

use TheShit\Vector\Contracts\EmbeddingClient;

class NullEmbeddings implements EmbeddingClient
{
    /**
     * @return array<float>
     */
    public function embed(string $text): array
    {
        return [];
    }

    /**
     * @param  array<string>  $texts
     * @return array<array<float>>
     */
    public function embedBatch(array $texts): array
    {
        return [];
    }
}
