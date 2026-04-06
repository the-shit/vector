<?php

declare(strict_types=1);

namespace TheShit\Vector\Requests\Collections;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class AliasRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<int, array{assign?: array{collection_name: string, alias_name: string}, delete?: array{alias_name: string}}>  $actions
     */
    public function __construct(
        protected readonly array $actions,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/collections/aliases';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'actions' => array_map(fn (array $action): array => match (true) {
                isset($action['assign']) => [
                    'create_alias' => [
                        'collection_name' => $action['assign']['collection_name'],
                        'alias_name' => $action['assign']['alias_name'],
                    ],
                ],
                isset($action['delete']) => [
                    'delete_alias' => [
                        'alias_name' => $action['delete']['alias_name'],
                    ],
                ],
                default => $action,
            }, $this->actions),
        ];
    }
}
