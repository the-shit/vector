# vector

[![Tests](https://img.shields.io/badge/tests-68%20passing-brightgreen)](https://github.com/the-shit/vector)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](https://github.com/the-shit/vector)
[![PHP](https://img.shields.io/badge/php-8.2%2B-blue)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

A thin, composable Saloon v3 connector for the Qdrant vector database.

## Overview

`the-shit/vector` wraps the Qdrant HTTP API behind a clean PHP interface without adding ceremony or abstraction layers you didn't ask for. It's built on [Saloon v3](https://docs.saloon.dev), so you get first-class mock support, middleware, and the full Saloon ecosystem out of the box.

The package ships a `VectorClient` interface and a concrete `Qdrant` implementation, a fluent filter builder, five typed readonly DTOs, and a Laravel ServiceProvider with zero-config auto-discovery. You can use it standalone or drop it into any Laravel 11/12 app.

The goals are simple: stay thin, stay typed, stay testable.

## Features

- **`QdrantConnector`** — Saloon connector with API key auth, configurable connect/request timeouts, and JSON headers wired by default
- **7 request classes** — create, delete, and get collections; upsert, search, scroll, and delete points
- **`QdrantFilter` builder** — fluent chainable filters: `must`, `mustNot`, `should`, `mustAny`, `mustRange`, and `fullText`
- **5 readonly DTOs** — `Point`, `ScoredPoint`, `CollectionInfo`, `UpsertResult`, `ScrollResult` with `fromArray` hydration
- **`VectorClient` interface** — type-hint against the contract; swap implementations in tests without friction
- **Laravel ServiceProvider** — auto-discovery, singleton bindings, config publishing, and environment variable support

## Quick Start

```bash
composer require the-shit/vector
```

```php
use TheShit\Vector\Qdrant;
use TheShit\Vector\QdrantConnector;

$client = new Qdrant(
    new QdrantConnector('http://localhost:6333', apiKey: 'your-key')
);

// Create a collection for 1536-dimensional OpenAI embeddings
$client->createCollection('documents', size: 1536);

// Upsert a point
$client->upsert('documents', [
    new Point('doc-1', $embedding, ['title' => 'Hello World']),
]);

// Search
$results = $client->search('documents', $queryEmbedding, limit: 5);

foreach ($results as $hit) {
    echo $hit->score . ' — ' . $hit->payload['title'] . PHP_EOL;
}
```

## Installation

### Prerequisites

- PHP 8.2+
- [Qdrant](https://qdrant.tech/documentation/guides/installation/) running locally or via cloud
- Composer

### Install

```bash
composer require the-shit/vector
```

### Laravel Setup

The package auto-discovers. Publish the config if you want to commit it:

```bash
php artisan vendor:publish --tag=vector-config
```

Add to `.env`:

```env
QDRANT_URL=http://localhost:6333
QDRANT_API_KEY=your-api-key
```

---

## Usage

### Collections

```php
// Create a collection (Cosine distance, 1536 dims)
$client->createCollection('documents', size: 1536);

// Other distance metrics
$client->createCollection('images', size: 512, distance: 'Dot');
$client->createCollection('audio', size: 768, distance: 'Euclid');

// Inspect a collection
$info = $client->getCollection('documents');
echo $info->status;              // "green"
echo $info->pointsCount;         // 4200
echo $info->indexedVectorsCount; // 4200

// Delete
$client->deleteCollection('old-collection');
```

### Upserting Points

Pass `Point` DTOs or raw arrays — both are accepted:

```php
use TheShit\Vector\Data\Point;

// Using Point DTOs (recommended)
$client->upsert('documents', [
    new Point('doc-1', $embedding1, ['title' => 'Article One', 'category' => 'tech']),
    new Point('doc-2', $embedding2, ['title' => 'Article Two', 'category' => 'science']),
]);

// Using raw arrays
$client->upsert('documents', [
    ['id' => 'doc-3', 'vector' => $embedding3, 'payload' => ['title' => 'Article Three']],
]);

// Check result
$result = $client->upsert('documents', $points);
$result->completed();    // true when status === 'completed'
$result->status;         // "completed"
$result->operationId;    // 42
```

### Searching

```php
// Basic search
$results = $client->search('documents', $queryVector, limit: 10);

foreach ($results as $hit) {
    echo $hit->id;      // "doc-1"
    echo $hit->score;   // 0.94
    echo $hit->payload['title'];
}

// Search with a filter
$filter = QdrantFilter::where('category', 'tech')->toArray();
$results = $client->search('documents', $queryVector, limit: 10, filter: $filter);
```

### Scrolling

Scroll through all points in a collection without a query vector. Supports cursor-based pagination:

```php
// First page
$page = $client->scroll('documents', limit: 100);

foreach ($page->points as $point) {
    echo $point->id . PHP_EOL;
}

// Paginate
while ($page->hasMore()) {
    $page = $client->scroll('documents', limit: 100, offset: $page->nextOffset);

    foreach ($page->points as $point) {
        // ...
    }
}
```

### Deleting Points

```php
// Delete by IDs
$client->delete('documents', ids: ['doc-1', 'doc-2', 42]);

// Delete by filter (e.g. archive sweep)
$filter = QdrantFilter::where('status', 'archived')->toArray();
$client->delete('documents', filter: $filter);
```

---

## Filters

`QdrantFilter` builds Qdrant filter payloads with a fluent interface. Call `toArray()` to get the raw array to pass to `search`, `scroll`, or `delete`.

### Basic Matching

```php
use TheShit\Vector\Filters\QdrantFilter;

// Single condition (static entry point)
$filter = QdrantFilter::where('category', 'music')->toArray();

// Chain multiple must conditions
$filter = QdrantFilter::where('type', 'track')
    ->must('status', 'active')
    ->toArray();

// Must not
$filter = (new QdrantFilter)
    ->mustNot('status', 'archived')
    ->toArray();

// Should (OR semantics)
$filter = (new QdrantFilter)
    ->should('genre', 'rock')
    ->should('genre', 'jazz')
    ->toArray();
```

### Match Any (IN clause)

```php
$filter = (new QdrantFilter)
    ->mustAny('genre', ['rock', 'punk', 'metal'])
    ->toArray();
```

### Range

```php
// Between 0.5 and 1.0
$filter = (new QdrantFilter)
    ->mustRange('energy', gte: 0.5, lte: 1.0)
    ->toArray();

// Greater than 120 BPM
$filter = (new QdrantFilter)
    ->mustRange('tempo', gt: 120.0)
    ->toArray();
```

Supported bounds: `gte`, `lte`, `gt`, `lt`. Null values are omitted from the output.

### Full-Text Search

```php
$filter = (new QdrantFilter)
    ->fullText('description', 'punk rock')
    ->toArray();
```

### Combining Conditions

```php
$filter = QdrantFilter::where('type', 'track')
    ->must('status', 'active')
    ->mustNot('explicit', true)
    ->mustAny('genre', ['rock', 'metal'])
    ->mustRange('energy', gte: 0.6)
    ->should('mood', 'hype')
    ->toArray();
```

Empty condition arrays are stripped automatically — a `new QdrantFilter` with nothing added returns `[]`.

---

## Laravel Integration

### Dependency Injection

The `VectorClient` interface is bound to `Qdrant` as a singleton. Type-hint against the interface anywhere Laravel resolves dependencies:

```php
use TheShit\Vector\Contracts\VectorClient;

class EmbeddingService
{
    public function __construct(
        private readonly VectorClient $vector,
    ) {}

    public function similar(array $embedding): array
    {
        return $this->vector->search('documents', $embedding, limit: 5);
    }
}
```

### Configuration

After publishing, `config/vector.php`:

```php
return [
    'url'     => env('QDRANT_URL', 'http://localhost:6333'),
    'api_key' => env('QDRANT_API_KEY'),

    'timeout' => [
        'connect' => (int) env('QDRANT_CONNECT_TIMEOUT', 10),
        'request' => (int) env('QDRANT_REQUEST_TIMEOUT', 30),
    ],
];
```

### Environment Variables

| Variable | Description | Default |
|---|---|---|
| `QDRANT_URL` | Qdrant base URL | `http://localhost:6333` |
| `QDRANT_API_KEY` | API key (optional for local) | — |
| `QDRANT_CONNECT_TIMEOUT` | Connection timeout in seconds | `10` |
| `QDRANT_REQUEST_TIMEOUT` | Request timeout in seconds | `30` |

---

## Architecture

```
src/
├── Qdrant.php                  # VectorClient implementation
├── QdrantConnector.php         # Saloon connector (auth, base URL, timeouts)
├── VectorServiceProvider.php   # Laravel auto-discovery, singleton bindings
│
├── Contracts/
│   ├── VectorClient.php        # Primary interface for DI
│   └── FilterBuilder.php       # Contract for toArray()
│
├── Filters/
│   └── QdrantFilter.php        # Fluent filter builder
│
├── Data/                       # Readonly DTOs
│   ├── Point.php
│   ├── ScoredPoint.php
│   ├── CollectionInfo.php
│   ├── UpsertResult.php
│   └── ScrollResult.php
│
└── Requests/                   # Saloon request classes
    ├── Collections/
    │   ├── CreateCollectionRequest.php
    │   ├── DeleteCollectionRequest.php
    │   └── GetCollectionRequest.php
    └── Points/
        ├── UpsertPointsRequest.php
        ├── SearchPointsRequest.php
        ├── ScrollPointsRequest.php
        └── DeletePointsRequest.php
```

HTTP flow:

```
Application
    │
    ▼
VectorClient (interface)
    │
    ▼
Qdrant          ─────▶  QdrantConnector  ─────▶  Qdrant HTTP API
(operations)             (Saloon, auth,            (localhost:6333
                          timeouts)                 or cloud)
```

---

## Testing

The suite uses Pest v4 with Saloon's built-in `MockClient` — no HTTP calls, no running Qdrant instance required.

```bash
./vendor/bin/pest
```

### Writing Tests

Mock individual request classes against canned responses:

```php
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use TheShit\Vector\Qdrant;
use TheShit\Vector\QdrantConnector;
use TheShit\Vector\Requests\Points\SearchPointsRequest;

$mock = new MockClient([
    SearchPointsRequest::class => MockResponse::make([
        'result' => [
            ['id' => 'doc-1', 'score' => 0.95, 'payload' => ['title' => 'Result']],
        ],
        'status' => 'ok',
    ]),
]);

$connector = new QdrantConnector('http://localhost:6333', 'test-key');
$connector->withMockClient($mock);
$client = new Qdrant($connector);

$results = $client->search('documents', [0.1, 0.2], limit: 1);

expect($results[0]->score)->toBe(0.95);
$mock->assertSent(SearchPointsRequest::class);
```

### In Laravel Tests

Bind a mock in your `TestCase` or individual test:

```php
use TheShit\Vector\Contracts\VectorClient;

$this->mock(VectorClient::class)
    ->shouldReceive('search')
    ->once()
    ->andReturn([]);
```

### Code Quality

```bash
# Linting
./vendor/bin/pint

# Static analysis / refactoring
./vendor/bin/rector --dry-run
```

---

## Development

### Local Setup

```bash
git clone https://github.com/the-shit/vector.git
cd vector
composer install
```

### Running the Full Suite

```bash
./vendor/bin/pest --coverage
```

### Project Standards

- Strict types on every file (`declare(strict_types=1)`)
- Readonly DTOs throughout
- No external dependencies beyond `saloonphp/saloon` at runtime
- Laravel framework is a dev dependency only — this package works standalone

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/your-feature`
3. Write tests for any new behaviour
4. Run `./vendor/bin/pest`, `./vendor/bin/pint`, and `./vendor/bin/rector --dry-run`
5. Open a pull request

Bug reports and feature requests welcome via [GitHub Issues](https://github.com/the-shit/vector/issues).

---

## License

MIT — see [LICENSE](LICENSE).

## Credits

Built by [Jordan Partridge](https://partridge.rocks). Powered by [Saloon](https://docs.saloon.dev) and [Qdrant](https://qdrant.tech).
