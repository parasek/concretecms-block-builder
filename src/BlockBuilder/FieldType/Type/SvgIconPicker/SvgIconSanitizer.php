<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Type\SvgIconPicker;

use BlockBuilder\Block\Validation\BlockConfigLimits;
use DOMDocument;
use enshrined\svgSanitize\Sanitizer;
use Throwable;

final readonly class SvgIconSanitizer
{
    private const array CSS_REFERENCE_ATTRIBUTE_NAMES = [
        'clip-path',
        'cursor',
        'fill',
        'filter',
        'marker',
        'marker-end',
        'marker-mid',
        'marker-start',
        'mask',
        'stroke',
        'style',
    ];

    public static function sanitize(string $svg): ?string
    {
        $svg = trim($svg);
        if ($svg === '' || strlen($svg) > BlockConfigLimits::MAX_SVG_CONTENT_LENGTH) {
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

        if (!self::removeNonFragmentReferences($document)) {
            return trim($sanitizedSvg);
        }

        $sanitizedSvg = $document->saveXML($document->documentElement);

        return is_string($sanitizedSvg) && trim($sanitizedSvg) !== ''
            ? trim($sanitizedSvg)
            : null;
    }

    private static function removeNonFragmentReferences(DOMDocument $document): bool
    {
        $referencesChanged = false;
        $elementsToRemove = [];
        foreach ($document->getElementsByTagName('*') as $element) {
            if (
                strtolower((string) $element->localName) === 'style'
                && self::containsUnsafeCssReference($element->textContent)
            ) {
                $elementsToRemove[] = $element;
                continue;
            }

            $referencesToRemove = [];
            foreach ($element->attributes as $attribute) {
                $attributeName = strtolower((string) $attribute->localName);
                if ($attributeName === 'href' || $attributeName === 'src') {
                    $reference = trim($attribute->value);
                    if (!str_starts_with($reference, '#')) {
                        $referencesToRemove[] = $attribute;
                        continue;
                    }

                    if ($reference !== $attribute->value) {
                        $attribute->value = $reference;
                        $referencesChanged = true;
                    }
                }

                if (
                    in_array($attributeName, self::CSS_REFERENCE_ATTRIBUTE_NAMES, true)
                    && self::containsUnsafeCssReference($attribute->value)
                ) {
                    $referencesToRemove[] = $attribute;
                }
            }

            foreach ($referencesToRemove as $attribute) {
                $element->removeAttributeNode($attribute);
                $referencesChanged = true;
            }
        }

        foreach ($elementsToRemove as $element) {
            $element->parentNode?->removeChild($element);
            $referencesChanged = true;
        }

        return $referencesChanged;
    }

    private static function containsUnsafeCssReference(string $css): bool
    {
        if (preg_match('/@import|\/\*|\\\\/i', $css) === 1) {
            return true;
        }
        if (preg_match('/\b(?:src|image|image-set|cross-fade)\s*\(/i', $css) === 1) {
            return true;
        }

        $urlTokenCount = preg_match_all('/url\s*\(/i', $css);
        $matchResult = preg_match_all('/url\s*\(\s*([^)]*?)\s*\)/is', $css, $matches);
        if ($urlTokenCount === false || $matchResult === false || $urlTokenCount !== $matchResult) {
            return true;
        }
        if ($matchResult === 0) {
            return preg_match('/url\s*\(/i', $css) === 1;
        }

        foreach ($matches[1] as $reference) {
            $reference = trim($reference);
            $firstCharacter = $reference[0] ?? '';
            if ($firstCharacter === '"' || $firstCharacter === "'") {
                if (!str_ends_with($reference, $firstCharacter)) {
                    return true;
                }
                $reference = trim(substr($reference, 1, -1));
            } elseif (str_contains($reference, '"') || str_contains($reference, "'")) {
                return true;
            }

            if (preg_match('/\A#[^\s"\'()<>]+\z/u', $reference) !== 1) {
                return true;
            }
        }

        return false;
    }
}
