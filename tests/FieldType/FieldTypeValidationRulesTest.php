<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\FieldType;

use BlockBuilder\Block\Validation\BlockConfigLimits;
use BlockBuilder\Block\Validation\Validator\FieldType\FieldTypeValidator;
use BlockBuilder\FieldType\Enum\FieldTypeContextEnum;
use BlockBuilder\FieldType\FieldTypeRegistry;
use BlockBuilder\FieldType\Type\SvgIconPicker\SvgIconSanitizer;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Field validation and SVG security component test.
 *
 * Verifies shared and field-specific validation feedback, accepted boundary values, and safe SVG
 * sanitization of executable, external, imported, or entity-based content.
 */
final class FieldTypeValidationRulesTest extends BlockBuilderTestCase
{
    /**
     * Confirms that each shared field rule produces the intended message, form path, and tab marker.
     *
     * @dataProvider sharedFieldRuleProvider
     */
    public function testSharedFieldRulesMapToTheCorrectFeedback(
        array $overrides,
        string $expectedMessage,
        string $expectedProperty,
    ): void {
        $fieldType = $this->getService(FieldTypeRegistry::class)->findByHandle('text_field');
        self::assertNotNull($fieldType);
        $field = [
            'fieldType' => 'text_field',
            'label' => 'Example field',
            'handle' => 'exampleField',
            'required' => '0',
            'helpText' => '',
            ...$fieldType::getDefaultValues(),
            ...$overrides,
        ];

        $feedback = $this->getService(FieldTypeValidator::class)->validate([
            'basic' => [$field],
            'entries' => [],
        ]);

        self::assertSame([$expectedMessage], $feedback->errors);
        self::assertSame(['basic[0][' . $expectedProperty . ']'], $feedback->fieldsWithError);
        self::assertSame(['tab-basic-information'], $feedback->tabsWithError);
    }

    public static function sharedFieldRuleProvider(): array
    {
        return [
            'missing field type' => [
                ['fieldType' => ''],
                'Some "Field type" fields are empty (Tab: Basic information).',
                'fieldType',
            ],
            'unknown field type' => [
                ['fieldType' => 'unknown_field_type'],
                'Some "Field type" fields contain an unsupported value (Tab: Basic information).',
                'fieldType',
            ],
            'empty label' => [
                ['label' => ''],
                'Some "Label" fields are empty (Tab: Basic information).',
                'label',
            ],
            'short label' => [
                ['label' => 'ab'],
                'Some "Label" fields contain fewer than 3 characters (Tab: Basic information).',
                'label',
            ],
            'empty handle' => [
                ['handle' => ''],
                'Some "Handle" fields are empty (Tab: Basic information).',
                'handle',
            ],
            'short handle' => [
                ['handle' => 'ab'],
                'Some "Handle" fields contain fewer than 3 characters (Tab: Basic information).',
                'handle',
            ],
            'long handle' => [
                ['handle' => str_repeat('a', 51)],
                'Some "Handle" fields contain more than 50 characters (Tab: Basic information).',
                'handle',
            ],
            'invalid handle characters' => [
                ['handle' => 'example1'],
                'Some "Handle" fields contain characters other than a-zA-Z_ (Tab: Basic information).',
                'handle',
            ],
            'handle boundary underscore' => [
                ['handle' => 'example_'],
                'Some "Handle" fields start or end with an underscore (Tab: Basic information).',
                'handle',
            ],
            'consecutive handle underscores' => [
                ['handle' => 'example__field'],
                'Some "Handle" fields contain two or more consecutive underscores (Tab: Basic information).',
                'handle',
            ],
            'uppercase first handle character' => [
                ['handle' => 'Example'],
                'Some "Handle" fields start with an uppercase character (Tab: Basic information).',
                'handle',
            ],
            'reserved handle' => [
                ['handle' => 'controller'],
                'Some "Handle" fields use forbidden words (Tab: Basic information).',
                'handle',
            ],
        ];
    }

    /**
     * Confirms that labels and handles exactly at their valid minimum and maximum boundaries pass validation.
     */
    public function testSharedFieldBoundariesAreAccepted(): void
    {
        $fieldType = $this->getService(FieldTypeRegistry::class)->findByHandle('text_field');
        self::assertNotNull($fieldType);
        $common = [
            'fieldType' => 'text_field',
            'required' => '0',
            'helpText' => '',
            ...$fieldType::getDefaultValues(),
        ];

        $feedback = $this->getService(FieldTypeValidator::class)->validate([
            'basic' => [
                [...$common, 'label' => 'abc', 'handle' => 'abc'],
                [...$common, 'label' => 'Exactly fifty characters', 'handle' => str_repeat('a', 50)],
            ],
            'entries' => [],
        ]);

        self::assertSame([], $feedback->errors);
        self::assertSame([], $feedback->fieldsWithError);
        self::assertSame([], $feedback->tabsWithError);
    }

    /**
     * Confirms that every field-specific invalid value maps to its declared messages and exact form paths.
     *
     * @dataProvider fieldSpecificRuleProvider
     */
    public function testEveryFieldSpecificRuleMapsToTheCorrectFeedback(
        string $fieldTypeHandle,
        array $overrides,
        array $expectedErrorKeys,
    ): void {
        $fieldType = $this->getService(FieldTypeRegistry::class)->findByHandle($fieldTypeHandle);
        self::assertNotNull($fieldType);
        $field = [
            'fieldType' => $fieldTypeHandle,
            'label' => 'Example field',
            'handle' => 'exampleField',
            'required' => '0',
            'helpText' => '',
            ...$fieldType::getDefaultValues(),
            ...$overrides,
        ];

        $feedback = $this->getService(FieldTypeValidator::class)->validate([
            'basic' => [$field],
            'entries' => [],
        ]);

        $messages = $fieldType::getErrorMessages(FieldTypeContextEnum::BasicFields);
        $expectedMessages = array_values(array_unique(array_map(
            static fn(string $errorKey): string => $messages[$errorKey],
            $expectedErrorKeys,
        )));
        $expectedFields = array_values(array_unique(array_map(
            static fn(string $errorKey): string => sprintf(
                'basic[0][%s]',
                explode('|', $errorKey, 2)[0],
            ),
            $expectedErrorKeys,
        )));

        self::assertSame($expectedMessages, $feedback->errors);
        self::assertSame($expectedFields, $feedback->fieldsWithError);
        self::assertSame(['tab-basic-information'], $feedback->tabsWithError);
    }

    public static function fieldSpecificRuleProvider(): array
    {
        $validSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1"><path d="M0 0h1v1z"></path></svg>';
        $validIcon = static fn(string $handle = 'square'): array => [
            'name' => 'Square',
            'handle' => $handle,
            'svg' => $validSvg,
        ];

        return [
            'text affix limits' => ['text_field', [
                'prefix' => str_repeat('x', 101),
                'suffix' => str_repeat('x', 101),
            ], ['prefix|too_long', 'suffix|too_long']],
            'text additional validation option' => ['text_field', [
                'additionalValidation' => 'postal_code',
            ], ['additionalValidation|invalid_option']],
            'text minimum length' => ['text_field', [
                'minimumLength' => '-1',
            ], ['minimumLength|invalid_number']],
            'text maximum length' => ['text_field', [
                'maximumLength' => '0',
            ], ['maximumLength|invalid_number']],
            'text inverted length range' => ['text_field', [
                'minimumLength' => '10',
                'maximumLength' => '5',
            ], ['minimumLength|greater_than_maximum']],
            'text default outside length range' => ['text_field', [
                'minimumLength' => '3',
                'maximumLength' => '5',
                'defaultValue' => 'xy',
            ], ['defaultValue|outside_length']],
            'text default fails email validation' => ['text_field', [
                'additionalValidation' => 'email',
                'defaultValue' => 'not-an-email',
            ], ['defaultValue|invalid_additional_validation']],

            'number size format' => ['number', ['size' => '10'], ['size|invalid_format']],
            'number step format' => ['number', ['step' => 'not-a-number'], ['step|invalid_format']],
            'number positive step' => ['number', ['step' => '0'], ['step|not_positive']],
            'number minimum format' => ['number', ['minimum' => 'minimum'], ['minimum|invalid_format']],
            'number maximum format' => ['number', ['maximum' => 'maximum'], ['maximum|invalid_format']],
            'number inverted range' => ['number', [
                'minimum' => '2',
                'maximum' => '1',
            ], ['minimum|greater_than_maximum']],
            'number displayed decimals' => ['number', [
                'displayedDecimals' => '21',
            ], ['displayedDecimals|invalid_number']],
            'number decimal separator' => ['number', [
                'displayedDecimalSeparator' => '',
            ], ['displayedDecimalSeparator|invalid_value']],
            'number default format' => ['number', [
                'defaultValue' => 'not-a-number',
            ], ['defaultValue|invalid_number']],
            'number default outside range' => ['number', [
                'minimum' => '0',
                'maximum' => '1',
                'defaultValue' => '2',
            ], ['defaultValue|outside_range']],
            'number default violates step' => ['number', [
                'minimum' => '0',
                'maximum' => '1',
                'step' => '0.2',
                'defaultValue' => '0.3',
            ], ['defaultValue|invalid_step']],
            'number affix limits' => ['number', [
                'prefix' => str_repeat('x', 101),
                'suffix' => str_repeat('x', 101),
            ], ['prefix|too_long', 'suffix|too_long']],

            'textarea minimum height' => ['textarea', ['minHeight' => '65'], ['minHeight|invalid_number']],
            'textarea maximum height' => ['textarea', ['maxHeight' => '2001'], ['maxHeight|invalid_number']],
            'textarea inverted height range' => ['textarea', [
                'minHeight' => '100',
                'maxHeight' => '99',
            ], ['minHeight|greater_than_maximum']],
            'textarea minimum length' => ['textarea', ['minimumLength' => '-1'], ['minimumLength|invalid_number']],
            'textarea maximum length' => ['textarea', ['maximumLength' => '0'], ['maximumLength|invalid_number']],
            'textarea inverted length range' => ['textarea', [
                'minimumLength' => '10',
                'maximumLength' => '5',
            ], ['minimumLength|greater_than_maximum']],
            'textarea default outside length range' => ['textarea', [
                'minimumLength' => '3',
                'maximumLength' => '5',
                'defaultValue' => 'xy',
            ], ['defaultValue|outside_length']],

            'WYSIWYG minimum height' => ['wysiwyg_editor', ['minHeight' => '39'], ['minHeight|invalid_number']],
            'WYSIWYG maximum height' => ['wysiwyg_editor', ['maxHeight' => '2001'], ['maxHeight|invalid_number']],
            'WYSIWYG inverted range' => ['wysiwyg_editor', [
                'minHeight' => '100',
                'maxHeight' => '99',
            ], ['minHeight|greater_than_maximum']],
            'WYSIWYG custom config must be an object' => ['wysiwyg_editor', [
                'customConfig' => '[]',
            ], ['customConfig|invalid_json']],
            'HTML editor height' => ['html_editor', ['height' => '39'], ['height|invalid_number']],

            'date minimum' => ['date_picker', ['minDate' => '2025-02-29'], ['minDate|invalid']],
            'date maximum' => ['date_picker', ['maxDate' => 'not-a-date'], ['maxDate|invalid']],
            'date inverted range' => ['date_picker', [
                'minDate' => '2025-01-02',
                'maxDate' => '2025-01-01',
            ], ['dateRange|invalid']],
            'date minute divisor' => ['date_picker', ['minuteInterval' => '7'], ['minuteInterval|invalid']],

            'image thumbnail requires dimensions' => ['image', [
                'createThumbnailImage' => '1',
                'thumbnailCrop' => '0',
                'thumbnailWidth' => '',
                'thumbnailHeight' => '',
            ], ['thumbnailOptions|empty_width_and_height']],
            'image thumbnail crop requires both dimensions' => ['image', [
                'createThumbnailImage' => '1',
                'thumbnailCrop' => '1',
                'thumbnailWidth' => '100',
                'thumbnailHeight' => '',
            ], ['thumbnailOptions|crop_requires_width_and_height']],
            'image thumbnail numeric dimensions' => ['image', [
                'thumbnailWidth' => '0',
                'thumbnailHeight' => '-1',
            ], ['thumbnailWidth|invalid_number', 'thumbnailHeight|invalid_number']],
            'image fullscreen requires dimensions' => ['image', [
                'createFullscreenImage' => '1',
                'fullscreenWidth' => '',
                'fullscreenHeight' => '',
            ], ['fullscreenOptions|empty_width_and_height']],
            'image fullscreen crop requires both dimensions' => ['image', [
                'createFullscreenImage' => '1',
                'fullscreenCrop' => '1',
                'fullscreenWidth' => '100',
                'fullscreenHeight' => '',
            ], ['fullscreenOptions|crop_requires_width_and_height']],
            'image fullscreen numeric dimensions' => ['image', [
                'fullscreenWidth' => '0',
                'fullscreenHeight' => '-1',
            ], ['fullscreenWidth|invalid_number', 'fullscreenHeight|invalid_number']],

            'single choice allow lists' => ['select_field', [
                'displayType' => 'dropdown',
                'addEmptyOption' => 'yes',
                'listGenerationMethod' => 'runtime',
                'options' => 'first :: First',
            ], [
                'displayType|invalid_option',
                'addEmptyOption|invalid_option',
                'listGenerationMethod|invalid_option',
            ]],
            'single choice options required' => ['select_field', ['options' => ''], ['options|empty']],
            'single choice option grammar' => ['select_field', [
                'options' => 'first :: First :: Extra',
            ], ['options|invalid_data']],
            'single choice unique keys' => ['select_field', [
                'options' => "first :: First\nfirst :: Duplicate",
            ], ['options|invalid_data']],
            'single choice default' => ['select_field', [
                'options' => 'first :: First',
                'defaultValue' => 'missing',
            ], ['defaultValue|invalid_option']],

            'multiple choice allow lists' => ['select_multiple_field', [
                'displayType' => 'multi_dropdown',
                'listGenerationMethod' => 'runtime',
                'options' => 'first :: First',
            ], ['displayType|invalid_option', 'listGenerationMethod|invalid_option']],
            'multiple choice options required' => ['select_multiple_field', ['options' => ''], ['options|empty']],
            'multiple choice option grammar' => ['select_multiple_field', [
                'options' => 'first :: First :: Extra',
            ], ['options|invalid_data']],
            'multiple choice unique keys' => ['select_multiple_field', [
                'options' => "first :: First\nfirst :: Duplicate",
            ], ['options|invalid_data']],
            'multiple choice default grammar' => ['select_multiple_field', [
                'options' => 'first :: First',
                'defaultValue' => 'first|first',
            ], ['defaultValue|invalid_data']],
            'multiple choice default option' => ['select_multiple_field', [
                'options' => 'first :: First',
                'defaultValue' => 'missing',
            ], ['defaultValue|invalid_option']],

            'file order allow list' => ['files_from_folder', ['fileOrder' => 'newest'], ['fileOrder|invalid_option']],
            'Express handle required' => ['express', ['expressHandle' => ''], ['expressHandle|empty']],
            'Express handle format' => ['express', ['expressHandle' => 'Invalid-Handle'], ['expressHandle|invalid']],

            'SVG icons required' => ['svg_icon_picker', ['icons' => []], ['icons|empty']],
            'SVG icon limit' => ['svg_icon_picker', [
                'icons' => array_fill(0, BlockConfigLimits::MAX_SVG_ICONS_PER_FIELD + 1, $validIcon()),
            ], ['icons|too_many']],
            'SVG icon definition' => ['svg_icon_picker', ['icons' => [['name' => '', 'handle' => 'square', 'svg' => $validSvg]]], ['icons|invalid_definition']],
            'SVG icon handle' => ['svg_icon_picker', ['icons' => [$validIcon('Invalid-Handle')]], ['icons|invalid_handle']],
            'SVG icon handles unique' => ['svg_icon_picker', [
                'icons' => [$validIcon(), $validIcon()],
            ], ['icons|duplicate_handle']],
            'SVG content' => ['svg_icon_picker', ['icons' => [[
                'name' => 'Invalid',
                'handle' => 'invalid',
                'svg' => 'not an SVG document',
            ]]], ['icons|invalid_svg']],

            'color value' => ['color_picker', ['defaultValue' => 'rgb(256, 0, 0)'], ['defaultValue|invalid_color']],
            'icon classes' => ['icon_picker', ['defaultValue' => 'icon<script>'], ['defaultValue|invalid_icon']],
        ];
    }

    /**
     * Confirms that the data provider includes a regression case for every registered field-specific error key.
     */
    public function testProviderCoversEveryDeclaredFieldSpecificError(): void
    {
        $coveredKeysByFieldType = [];
        foreach (self::fieldSpecificRuleProvider() as [$fieldTypeHandle, , $errorKeys]) {
            $coveredKeysByFieldType[$fieldTypeHandle] = [
                ...($coveredKeysByFieldType[$fieldTypeHandle] ?? []),
                ...$errorKeys,
            ];
        }

        foreach ($this->getService(FieldTypeRegistry::class)->all() as $fieldType) {
            $fieldTypeHandle = $fieldType::getFieldType()->value;
            $declaredErrorKeys = array_keys($fieldType::getErrorMessages(FieldTypeContextEnum::BasicFields));
            $coveredErrorKeys = array_values(array_unique($coveredKeysByFieldType[$fieldTypeHandle] ?? []));
            sort($declaredErrorKeys);
            sort($coveredErrorKeys);

            self::assertSame(
                $declaredErrorKeys,
                $coveredErrorKeys,
                sprintf('Every validation error declared by "%s" must have a regression case.', $fieldTypeHandle),
            );
        }
    }

    /**
     * Confirms that SVG scripts and event-handler attributes are removed while the document remains usable.
     */
    public function testSvgSanitizerRemovesExecutableContent(): void
    {
        $sanitizedSvg = SvgIconSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" onload="alert(1)">'
            . '<script>alert(1)</script>'
            . '<path onclick="alert(1)" d="M0 0h1v1z"></path>'
            . '</svg>',
        );

        self::assertNotNull($sanitizedSvg);
        self::assertStringNotContainsStringIgnoringCase('<script', $sanitizedSvg);
        self::assertStringNotContainsStringIgnoringCase('onload', $sanitizedSvg);
        self::assertStringNotContainsStringIgnoringCase('onclick', $sanitizedSvg);
    }

    /**
     * Confirms that external, local-path, data, and executable references are removed from SVG link attributes.
     *
     * @dataProvider externalSvgReferenceProvider
     */
    public function testSvgSanitizerRemovesExternalReferences(string $attribute, string $reference): void
    {
        $namespace = str_starts_with($attribute, 'xlink:')
            ? ' xmlns:xlink="http://www.w3.org/1999/xlink"'
            : '';
        $sanitizedSvg = SvgIconSanitizer::sanitize(sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg"%s><image %s="%s"></image></svg>',
            $namespace,
            $attribute,
            htmlspecialchars($reference, ENT_QUOTES | ENT_XML1),
        ));

        self::assertNotNull($sanitizedSvg);
        self::assertStringNotContainsString($reference, $sanitizedSvg);
        self::assertDoesNotMatchRegularExpression('/(?:^|\s)(?:xlink:)?href\s*=/i', $sanitizedSvg);
    }

    public static function externalSvgReferenceProvider(): array
    {
        $references = [
            'http' => 'http://example.com/icon.svg',
            'https' => 'https://example.com/icon.svg',
            'protocol relative' => '//example.com/icon.svg',
            'data URI' => 'data:image/svg+xml;base64,PHN2Zz4=',
            'file URI' => 'file:///etc/passwd',
            'JavaScript URI' => 'javascript:alert(1)',
            'other scheme' => 'ftp://example.com/icon.svg',
            'absolute path' => '/assets/icon.svg',
            'relative path' => 'icons.svg#icon',
        ];
        $cases = [];
        foreach (['href', 'xlink:href'] as $attribute) {
            foreach ($references as $description => $reference) {
                $cases[$attribute . ' ' . $description] = [$attribute, $reference];
            }
        }

        return $cases;
    }

    /**
     * Confirms that unsafe resource URLs are removed from presentation attributes, inline styles, and style elements.
     *
     * @dataProvider externalCssSvgReferenceProvider
     */
    public function testSvgSanitizerRemovesExternalCssReferences(string $content, string $reference): void
    {
        $sanitizedSvg = SvgIconSanitizer::sanitize(sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg">%s</svg>',
            sprintf($content, htmlspecialchars($reference, ENT_QUOTES | ENT_XML1)),
        ));

        self::assertNotNull($sanitizedSvg);
        self::assertStringNotContainsString($reference, $sanitizedSvg);
    }

    public static function externalCssSvgReferenceProvider(): array
    {
        return [
            'presentation attribute with root-relative URL' => [
                '<path fill="url(%s)"></path>',
                '/tracking-pixel.svg',
            ],
            'presentation attribute with remote URL' => [
                '<path filter="url(&quot;%s&quot;)"></path>',
                'https://example.test/filter.svg#filter',
            ],
            'inline style with relative URL' => [
                '<path style="fill:url(%s)"></path>',
                'icons.svg#paint',
            ],
            'inline style with data URL' => [
                '<path style="stroke:url(&quot;%s&quot;)"></path>',
                'data:image/svg+xml;base64,PHN2Zz4=',
            ],
            'style element with remote URL' => [
                '<style>.icon{fill:url(%s)}</style><path class="icon"></path>',
                '//example.test/paint.svg#paint',
            ],
        ];
    }

    /**
     * Confirms that CSS imports and escape-obfuscated external URLs cannot bypass SVG sanitization.
     */
    public function testSvgSanitizerRemovesObfuscatedAndImportedCssReferences(): void
    {
        $sanitizedSvg = SvgIconSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<style>@import "https://example.test/icon.css";.safe{fill:red}</style>'
            . '<path class="safe" style="fill:u\\72l(/tracking-pixel.svg)"></path>'
            . '</svg>',
        );

        self::assertNotNull($sanitizedSvg);
        self::assertStringNotContainsStringIgnoringCase('@import', $sanitizedSvg);
        self::assertStringNotContainsString('example.test', $sanitizedSvg);
        self::assertStringNotContainsString('tracking-pixel.svg', $sanitizedSvg);
        self::assertDoesNotMatchRegularExpression('/(?:^|\s)style\s*=/i', $sanitizedSvg);
    }

    /**
     * Confirms that safe href and xlink references to elements inside the same SVG are preserved.
     */
    public function testSvgSanitizerPreservesLocalFragmentReferences(): void
    {
        $sanitizedSvg = SvgIconSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">'
            . '<defs><path id="icon" d="M0 0h1v1z"></path></defs>'
            . '<use href="#icon"></use><use xlink:href="#icon"></use>'
            . '</svg>',
        );

        self::assertNotNull($sanitizedSvg);
        self::assertStringContainsString('href="#icon"', $sanitizedSvg);
        self::assertStringContainsString('xlink:href="#icon"', $sanitizedSvg);
    }

    /**
     * Confirms that safe CSS references to definitions inside the same SVG survive sanitization.
     */
    public function testSvgSanitizerPreservesLocalFragmentCssReferences(): void
    {
        $sanitizedSvg = SvgIconSanitizer::sanitize(
            '<svg xmlns="http://www.w3.org/2000/svg">'
            . '<defs><linearGradient id="paint"><stop offset="0"></stop></linearGradient></defs>'
            . '<style>.icon{fill:url(#paint)}</style>'
            . '<path class="icon" fill="url(#paint)" style="stroke:url(&quot;#paint&quot;)"></path>'
            . '</svg>',
        );

        self::assertNotNull($sanitizedSvg);
        self::assertStringContainsString('<style>.icon{fill:url(#paint)}</style>', $sanitizedSvg);
        self::assertStringContainsString('fill="url(#paint)"', $sanitizedSvg);
        self::assertStringContainsString('style="stroke:url(&quot;#paint&quot;)"', $sanitizedSvg);
    }

    /**
     * Confirms that an XML external entity cannot make the sanitizer read or expose a local file.
     */
    public function testSvgSanitizerDoesNotExpandXmlExternalEntities(): void
    {
        $svg = '<?xml version="1.0"?>'
            . '<!DOCTYPE svg [<!ENTITY payload SYSTEM "file:///etc/passwd">]>'
            . '<svg xmlns="http://www.w3.org/2000/svg"><text>&payload;</text></svg>';

        $sanitizedSvg = SvgIconSanitizer::sanitize($svg);

        if ($sanitizedSvg === null) {
            self::assertNull($sanitizedSvg);

            return;
        }

        self::assertStringNotContainsStringIgnoringCase('<!DOCTYPE', $sanitizedSvg);
        self::assertStringNotContainsStringIgnoringCase('<!ENTITY', $sanitizedSvg);
        self::assertStringNotContainsStringIgnoringCase('file://', $sanitizedSvg);
        self::assertMatchesRegularExpression('/<text(?:><\/text>|\s*\/>)/', $sanitizedSvg);
    }
}
