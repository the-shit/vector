<?php

declare(strict_types=1);

namespace TheShit\Vector\Data;

final readonly class Point
{
    /**
     * @param  array<float>  $vector
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public string|int $id,
        public array $vector,
        public array $payload = [],
    ) {}

    /**
     * @return array{id: string|int, vector: array<float>, payload: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'vector' => $this->vector,
            'payload' => $this->payload,
        ];
    }
}
