<?php

declare(strict_types=1);

namespace TheShit\Vector\Contracts;

interface FilterBuilder
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
