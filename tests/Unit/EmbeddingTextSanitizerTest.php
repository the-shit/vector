<?php

declare(strict_types=1);

use TheShit\Vector\Embeddings\EmbeddingTextSanitizer;

describe('EmbeddingTextSanitizer', function (): void {
    it('leaves plain ascii text untouched', function (): void {
        $text = "# Heading\n\nSome prose with code: `php artisan test` and a [link](https://example.com).";

        expect(EmbeddingTextSanitizer::sanitize($text))->toBe($text);
    });

    it('strips emoji and pictographs', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("Fleet \u{1F692} module"))->toBe('Fleet module')
            ->and(EmbeddingTextSanitizer::sanitize("done \u{2705} failed \u{274C}"))->toBe('done failed');
    });

    it('strips variation selectors and zero-width joiners', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("plain\u{FE0F} text\u{200D}here"))->toBe('plain texthere');
    });

    it('normalizes smart punctuation to ascii', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("\u{201C}it\u{2019}s fine\u{201D} \u{2014} mostly\u{2026}"))
            ->toBe('"it\'s fine" - mostly...');
    });

    it('converts common arrows to ascii', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("Service \u{2192} Action \u{2192} Data"))
            ->toBe('Service -> Action -> Data');
    });

    it('strips box drawing characters from ascii trees', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("\u{251C}\u{2500}\u{2500} src"))->toBe(' src');
    });

    it('converts bullets to dashes', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("\u{2022} first item"))->toBe('- first item');
    });

    it('collapses repeated spaces left by stripping', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("a \u{1F600} \u{1F600} b"))->toBe('a b');
    });

    it('preserves newlines', function (): void {
        expect(EmbeddingTextSanitizer::sanitize("line one\nline two"))->toBe("line one\nline two");
    });
});
