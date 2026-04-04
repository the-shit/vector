<?php

declare(strict_types=1);

namespace TheShit\Vector\Data;

final readonly class UpsertResult
{
    public function __construct(
        public string $status,
        public ?int $operationId = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? 'unknown',
            operationId: $data['operation_id'] ?? null,
        );
    }

    public function completed(): bool
    {
        return $this->status === 'completed';
    }
}
