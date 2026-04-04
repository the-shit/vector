<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Collections;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class DeleteCollectionRequest extends Request
{
    protected Method $method = Method::DELETE;

    public function __construct(
        protected readonly string $name,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/'.$this->name;
    }
}
