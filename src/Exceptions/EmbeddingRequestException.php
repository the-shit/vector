<?php

declare(strict_types=1);

namespace TheShit\Vector\Exceptions;

use JsonException;
use RuntimeException;
use Saloon\Exceptions\Request\RequestException;

class EmbeddingRequestException extends RuntimeException
{
    public static function fromSaloon(string $model, RequestException $exception): self
    {
        $response = $exception->getResponse();
        $status = $response->status();

        try {
            // OpenAI nests the message ({"error": {"message": ...}}), Ollama
            // returns it flat ({"error": ...}).
            $error = $response->json('error.message') ?? $response->json('error');
        } catch (JsonException) {
            $error = null;
        }

        if (! is_string($error) || $error === '') {
            $error = $response->body();
        }

        return new self(
            "Embedding request failed for model [{$model}] (HTTP {$status}): {$error}",
            $status,
            $exception,
        );
    }
}
