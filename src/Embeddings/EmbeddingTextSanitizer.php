<?php

declare(strict_types=1);

namespace TheShit\Vector\Embeddings;

class EmbeddingTextSanitizer
{
    /**
     * Multi-byte punctuation that tokenizes poorly on BERT-style embedders,
     * mapped to ASCII equivalents that preserve meaning.
     */
    private const REPLACEMENTS = [
        "\u{2019}" => "'",
        "\u{2018}" => "'",
        "\u{201C}" => '"',
        "\u{201D}" => '"',
        "\u{2014}" => '-',
        "\u{2013}" => '-',
        "\u{2026}" => '...',
        "\u{2022}" => '-',
        "\u{2192}" => '->',
        "\u{2190}" => '<-',
        "\u{2191}" => '^',
        "\u{2193}" => 'v',
    ];

    /**
     * Emoji, pictographs, dingbats, box-drawing, geometric shapes, arrows,
     * variation selectors, and zero-width joiners. These carry no retrieval
     * signal but expand into many byte-fallback tokens on BERT-style
     * tokenizers, wasting a small context window (e.g. bge-large's 512
     * tokens) and triggering Ollama's truncation miscount, which rejects
     * the request with "input length exceeds the context length".
     */
    private const NOISE_PATTERN = '/[\x{20E3}\x{2190}-\x{21FF}\x{2300}-\x{23FF}\x{2500}-\x{25FF}\x{2600}-\x{27BF}\x{2B00}-\x{2BFF}\x{1F000}-\x{1FAFF}]/u';

    /**
     * Zero-width characters are deleted outright rather than replaced with
     * a space, since they never occupy visual space between words.
     */
    private const ZERO_WIDTH_PATTERN = '/[\x{200B}-\x{200D}\x{FE00}-\x{FE0F}]/u';

    /**
     * Normalize text before embedding. Lossy by design: the sanitized text
     * is only used to generate the vector, never stored.
     */
    public static function sanitize(string $text): string
    {
        $text = strtr($text, self::REPLACEMENTS);
        $text = (string) preg_replace(self::ZERO_WIDTH_PATTERN, '', $text);
        $text = (string) preg_replace(self::NOISE_PATTERN, ' ', $text);
        $text = (string) preg_replace('/ {2,}/', ' ', $text);

        return (string) preg_replace('/ +$/m', '', $text);
    }
}
