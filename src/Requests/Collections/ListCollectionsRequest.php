<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Collections;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class ListCollectionsRequest extends Request
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return '/collections';
    }
}
