<?php

declare(strict_types=1);

namespace TheShit\Vector\Embeddings\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class OllamaEmbedRequest extends Request implements HasBody
{
    use HasJsonBody;

    protected Method $method = Method::POST;

    /**
     * @param  array<string>  $texts
     */
    public function __construct(
        protected readonly string $model,
        protected readonly array $texts,
    ) {}

    public function resolveEndpoint(): string
    {
        return '/api/embed';
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultBody(): array
    {
        return [
            'model' => $this->model,
            'input' => $this->texts,
        ];
    }
}
