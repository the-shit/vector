<?php

declare(strict_types=1);

/**
 * Contract tests that validate our request/response shapes against
 * Qdrant's published OpenAPI spec. If Qdrant changes their API,
 * these tests fail before users do.
 */
function loadSpec(): array
{
    static $spec;
    if ($spec === null) {
        $path = __DIR__.'/qdrant-openapi.json';
        if (! file_exists($path)) {
            test()->markTestSkipped('OpenAPI spec not found. Run: composer run fetch-spec');
        }
        $spec = json_decode(file_get_contents($path), true);
    }

    return $spec;
}

function resolveSchema(array $spec, string $ref): array
{
    $path = str_replace('#/components/schemas/', '', $ref);

    return $spec['components']['schemas'][$path] ?? throw new RuntimeException("Schema not found: {$ref}");
}

function getRequestSchema(array $spec, string $method, string $path): ?array
{
    $body = $spec['paths'][$path][$method]['requestBody']['content']['application/json']['schema'] ?? null;
    if ($body === null) {
        return null;
    }

    return isset($body['$ref']) ? resolveSchema($spec, $body['$ref']) : $body;
}

function specFields(array $schema): array
{
    return array_keys($schema['properties'] ?? []);
}

function specRequired(array $schema): array
{
    return $schema['required'] ?? [];
}

describe('Contract: Request bodies match Qdrant OpenAPI spec', function (): void {

    it('CreateCollection accepts vectors field', function (): void {
        $schema = getRequestSchema(loadSpec(), 'put', '/collections/{collection_name}');
        expect(specFields($schema))->toContain('vectors');
    });

    it('SearchRequest has required vector and limit', function (): void {
        $schema = getRequestSchema(loadSpec(), 'post', '/collections/{collection_name}/points/search');
        $fields = specFields($schema);
        $required = specRequired($schema);

        expect($fields)->toContain('vector')
            ->and($fields)->toContain('limit')
            ->and($fields)->toContain('filter')
            ->and($fields)->toContain('with_payload')
            ->and($fields)->toContain('with_vector')
            ->and($required)->toContain('vector')
            ->and($required)->toContain('limit');
    });

    it('ScrollRequest accepts our fields', function (): void {
        $fields = specFields(getRequestSchema(loadSpec(), 'post', '/collections/{collection_name}/points/scroll'));

        expect($fields)->toContain('limit')
            ->and($fields)->toContain('offset')
            ->and($fields)->toContain('filter')
            ->and($fields)->toContain('with_payload')
            ->and($fields)->toContain('with_vector');
    });

    it('PointRequest requires ids', function (): void {
        $schema = getRequestSchema(loadSpec(), 'post', '/collections/{collection_name}/points');

        expect(specFields($schema))->toContain('ids')
            ->and(specFields($schema))->toContain('with_payload')
            ->and(specFields($schema))->toContain('with_vector')
            ->and(specRequired($schema))->toContain('ids');
    });

    it('CountRequest accepts filter and exact', function (): void {
        $fields = specFields(getRequestSchema(loadSpec(), 'post', '/collections/{collection_name}/points/count'));

        expect($fields)->toContain('filter')
            ->and($fields)->toContain('exact');
    });

    it('CreateFieldIndex requires field_name', function (): void {
        $schema = getRequestSchema(loadSpec(), 'put', '/collections/{collection_name}/index');

        expect(specFields($schema))->toContain('field_name')
            ->and(specRequired($schema))->toContain('field_name');
    });

    it('SetPayload requires payload field', function (): void {
        $schema = getRequestSchema(loadSpec(), 'post', '/collections/{collection_name}/points/payload');

        expect(specFields($schema))->toContain('payload')
            ->and(specFields($schema))->toContain('points')
            ->and(specRequired($schema))->toContain('payload');
    });
});

describe('Contract: Response DTOs match Qdrant OpenAPI spec', function (): void {

    it('ScoredPoint spec requires id, version, score', function (): void {
        $schema = resolveSchema(loadSpec(), '#/components/schemas/ScoredPoint');
        $required = specRequired($schema);

        expect($required)->toContain('id')
            ->and($required)->toContain('version')
            ->and($required)->toContain('score');
    });

    it('CollectionInfo spec requires status and segments_count', function (): void {
        $schema = resolveSchema(loadSpec(), '#/components/schemas/CollectionInfo');
        $required = specRequired($schema);

        expect($required)->toContain('status')
            ->and($required)->toContain('segments_count')
            ->and($required)->toContain('config');
    });

    it('UpdateResult spec requires status', function (): void {
        $schema = resolveSchema(loadSpec(), '#/components/schemas/UpdateResult');

        expect(specFields($schema))->toContain('status')
            ->and(specFields($schema))->toContain('operation_id')
            ->and(specRequired($schema))->toContain('status');
    });

    it('Record spec has id, payload, vector', function (): void {
        $fields = specFields(resolveSchema(loadSpec(), '#/components/schemas/Record'));

        expect($fields)->toContain('id')
            ->and($fields)->toContain('payload')
            ->and($fields)->toContain('vector');
    });
});

describe('Contract: Hybrid search (query endpoint) matches Qdrant OpenAPI spec', function (): void {

    it('QueryRequest accepts prefetch and query fields', function (): void {
        $schema = getRequestSchema(loadSpec(), 'post', '/collections/{collection_name}/points/query');
        $fields = specFields($schema);

        expect($fields)->toContain('prefetch')
            ->and($fields)->toContain('query')
            ->and($fields)->toContain('limit')
            ->and($fields)->toContain('with_payload')
            ->and($fields)->toContain('with_vector')
            ->and($fields)->toContain('filter');
    });

    it('CreateCollection accepts sparse_vectors field', function (): void {
        $schema = getRequestSchema(loadSpec(), 'put', '/collections/{collection_name}');

        expect(specFields($schema))->toContain('sparse_vectors');
    });
});

describe('Contract: Endpoints exist in Qdrant spec', function (): void {

    it('all our endpoints exist', function (): void {
        $paths = array_keys(loadSpec()['paths']);

        expect($paths)->toContain('/collections/{collection_name}')
            ->and($paths)->toContain('/collections/{collection_name}/points')
            ->and($paths)->toContain('/collections/{collection_name}/points/search')
            ->and($paths)->toContain('/collections/{collection_name}/points/scroll')
            ->and($paths)->toContain('/collections/{collection_name}/points/delete')
            ->and($paths)->toContain('/collections/{collection_name}/points/count')
            ->and($paths)->toContain('/collections/{collection_name}/points/payload')
            ->and($paths)->toContain('/collections/{collection_name}/index')
            ->and($paths)->toContain('/collections/{collection_name}/points/query')
            ->and($paths)->toContain('/collections/aliases');
    });

    it('our HTTP methods match', function (): void {
        $spec = loadSpec();

        expect($spec['paths']['/collections/{collection_name}'])->toHaveKey('put')
            ->and($spec['paths']['/collections/{collection_name}'])->toHaveKey('get')
            ->and($spec['paths']['/collections/{collection_name}'])->toHaveKey('delete')
            ->and($spec['paths']['/collections/{collection_name}/points'])->toHaveKey('put')
            ->and($spec['paths']['/collections/{collection_name}/points'])->toHaveKey('post')
            ->and($spec['paths']['/collections/{collection_name}/points/search'])->toHaveKey('post')
            ->and($spec['paths']['/collections/{collection_name}/points/scroll'])->toHaveKey('post')
            ->and($spec['paths']['/collections/{collection_name}/points/delete'])->toHaveKey('post')
            ->and($spec['paths']['/collections/{collection_name}/points/count'])->toHaveKey('post')
            ->and($spec['paths']['/collections/{collection_name}/points/payload'])->toHaveKey('post')
            ->and($spec['paths']['/collections/{collection_name}/index'])->toHaveKey('put')
            ->and($spec['paths']['/collections/{collection_name}/index/{field_name}'])->toHaveKey('delete')
            ->and($spec['paths']['/collections/{collection_name}/points/query'])->toHaveKey('post')
            ->and($spec['paths']['/collections/aliases'])->toHaveKey('post');
    });
});

describe('Contract: Alias request matches Qdrant OpenAPI spec', function (): void {

    it('ChangeAliasesRequest accepts actions array', function (): void {
        $schema = getRequestSchema(loadSpec(), 'post', '/collections/aliases');

        expect(specFields($schema))->toContain('actions')
            ->and(specRequired($schema))->toContain('actions');
    });
});
