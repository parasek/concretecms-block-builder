<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SvgIconPicker;

use DOMDocument;
use enshrined\svgSanitize\Sanitizer;
use Throwable;

final readonly class SvgIconSanitizer
{
    public const int MAX_SVG_LENGTH = 100_000;

    public static function sanitize(string $svg): ?string
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > self::MAX_SVG_LENGTH) {
            return null;
        }

        try {
            $sanitizer = new Sanitizer();
            $sanitizer->removeRemoteReferences(true);
            $sanitizer->removeXMLTag(true);
            $sanitizer->minify(true);
            $sanitizedSvg = $sanitizer->sanitize($svg);
        } catch (Throwable) {
            return null;
        }
        if (!is_string($sanitizedSvg) || trim($sanitizedSvg) === '') {
            return null;
        }

        $document = new DOMDocument();
        $previousErrorHandling = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadXML($sanitizedSvg, LIBXML_NONET | LIBXML_NOWARNING);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrorHandling);
        }

        if ($loaded !== true || strtolower((string) $document->documentElement?->localName) !== 'svg') {
            return null;
        }

        return trim($sanitizedSvg);
    }
}
