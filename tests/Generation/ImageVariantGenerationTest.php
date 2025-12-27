<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\FieldType\Enum\FieldTypeEnum;
use BlockBuilder\FieldType\Type\Image\ImageFieldType;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;

/**
 * Test type: Image variant generated-output contract test.
 *
 * Verifies that thumbnail and fullscreen editing code is generated only for enabled variants.
 */
final class ImageVariantGenerationTest extends BlockBuilderTestCase
{
    public function testSettingsAreOmittedWhenNoImageVariantIsEnabled(): void
    {
        $files = $this->generateImageBlockFiles(
            basic: [
                $this->createImageField('disabledImage', [
                    'createThumbnailImage' => false,
                    'thumbnailEditable' => true,
                    'createFullscreenImage' => false,
                    'fullscreenEditable' => true,
                ]),
            ],
        );

        self::assertStringNotContainsString("t('Settings')", $files['form.php']);
        self::assertStringNotContainsString('$userInterface->tabs(', $files['form.php']);
        self::assertStringNotContainsString('disabledImage_override_dimensions', $files['form.php']);
        self::assertStringNotContainsString('disabledImage_override_fullscreen_dimensions', $files['form.php']);
    }

    public function testRepeatableImageWithoutGeneratedVariantsStillBuildsThePlan(): void
    {
        $files = $this->generateImageBlockFiles(
            entries: [
                $this->createImageField('disabledRepeatableImage', [
                    'createThumbnailImage' => false,
                    'thumbnailEditable' => true,
                    'createFullscreenImage' => false,
                    'fullscreenEditable' => true,
                ]),
            ],
        );

        self::assertArrayHasKey('controller.php', $files);
        self::assertArrayHasKey('form.php', $files);
        self::assertArrayHasKey('view.php', $files);
        self::assertStringNotContainsString("t('Settings')", $files['form.php']);
    }

    public function testBasicImageVariantsRenderEditingControlsInsideBasicSection(): void
    {
        $files = $this->generateImageBlockFiles(
            basic: [
                $this->createImageField('disabledImage', [
                    'createThumbnailImage' => false,
                    'thumbnailEditable' => true,
                    'createFullscreenImage' => false,
                    'fullscreenEditable' => true,
                ]),
                $this->createImageField('thumbnailImage', [
                    'createThumbnailImage' => true,
                    'thumbnailEditable' => true,
                    'createFullscreenImage' => false,
                    'fullscreenEditable' => true,
                ]),
            ],
        );

        self::assertStringNotContainsString("t('Settings')", $files['form.php']);
        self::assertStringNotContainsString('$userInterface->tabs(', $files['form.php']);
        self::assertStringContainsString('thumbnailImage_override_dimensions', $files['form.php']);
        self::assertStringNotContainsString('thumbnailImage_override_fullscreen_dimensions', $files['form.php']);
        self::assertStringNotContainsString('disabledImage_override_dimensions', $files['form.php']);
        self::assertStringNotContainsString('disabledImage_override_fullscreen_dimensions', $files['form.php']);

        self::assertStringContainsString('thumbnailImage_thumbnailLink', $files['view.php']);
        self::assertStringNotContainsString('thumbnailImage_fullscreenLink', $files['view.php']);
        self::assertStringNotContainsString('disabledImage_thumbnailLink', $files['view.php']);
        self::assertStringNotContainsString('disabledImage_fullscreenLink', $files['view.php']);

        self::assertStringContainsString('thumbnailImage_override_dimensions', $files['controller.php']);
        self::assertStringNotContainsString('thumbnailImage_override_fullscreen_dimensions', $files['controller.php']);
        self::assertStringNotContainsString('disabledImage_override_dimensions', $files['controller.php']);
        self::assertStringNotContainsString('disabledImage_override_fullscreen_dimensions', $files['controller.php']);
    }

    public function testRepeatableImageVariantsControlSettingsAndGeneratedCode(): void
    {
        $files = $this->generateImageBlockFiles(
            entries: [
                $this->createImageField('repeatableImage', [
                    'createThumbnailImage' => false,
                    'thumbnailEditable' => true,
                    'createFullscreenImage' => true,
                    'fullscreenEditable' => true,
                ]),
            ],
        );

        self::assertStringContainsString("t('Settings')", $files['form.php']);
        self::assertStringContainsString('repeatableImage_override_fullscreen_dimensions', $files['form.php']);
        self::assertStringNotContainsString('repeatableImage_override_dimensions', $files['form.php']);
        self::assertStringContainsString('repeatableImage_fullscreenLink', $files['view.php']);
        self::assertStringNotContainsString('repeatableImage_thumbnailLink', $files['view.php']);
        self::assertStringContainsString('repeatableImage_override_fullscreen_dimensions', $files['controller.php']);
        self::assertStringNotContainsString('repeatableImage_override_dimensions', $files['controller.php']);
    }

    /**
     * @param array<int, array<string, mixed>> $basic
     * @param array<int, array<string, mixed>> $entries
     * @return array<string, string>
     */
    private function generateImageBlockFiles(array $basic = [], array $entries = []): array
    {
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockName' => 'Image Variant Test',
            'blockHandle' => 'image_variant_test',
            'basic' => $basic,
            'entries' => $entries,
        ]);
        $files = [];
        foreach ($this->generateTextFiles($this->createGenerationContext($config)) as $generatedFile) {
            $files[$generatedFile->relativePath] = $generatedFile->contents;
        }

        return $files;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function createImageField(string $handle, array $overrides): array
    {
        return [
            'fieldType' => FieldTypeEnum::Image->value,
            'label' => $handle,
            'handle' => $handle,
            'required' => false,
            'helpText' => '',
            ...ImageFieldType::getDefaultValues(),
            ...$overrides,
        ];
    }
}
