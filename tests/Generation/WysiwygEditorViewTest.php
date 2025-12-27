<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\FieldType\Type\WysiwygEditor\Generation\WysiwygEditorFieldGenerationContributor;
use BlockBuilder\FieldType\Type\WysiwygEditor\WysiwygEditorFieldType;
use BlockBuilder\FieldType\Type\WysiwygEditor\WysiwygEditorPresets;
use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use ReflectionMethod;

final class WysiwygEditorViewTest extends BlockBuilderTestCase
{
    /** @dataProvider outputProvider */
    public function testGeneratedViewFiltersTagsAndNormalizesEndings(string $allowedTags, string $content, string $expected): void
    {
        $field = WysiwygEditorFieldType::createDtoFromArray([
            'handle' => 'content',
            'allowedTags' => $allowedTags,
        ]);
        $contributor = $this->getService(WysiwygEditorFieldGenerationContributor::class);
        $render = new ReflectionMethod($contributor, 'renderViewFragment');

        foreach ([true, false] as $basicField) {
            $fragment = $render->invoke($contributor, $field, $basicField);
            self::assertSame($allowedTags !== '', str_contains($fragment, 'strip_tags('));
            $entry = ['content' => $content];
            ob_start();
            try {
                eval('?>' . $fragment);
                $output = ob_get_contents();
            } finally {
                ob_end_clean();
            }
            self::assertSame($expected, trim($output));
        }
    }

    public static function outputProvider(): array
    {
        return [
            'all tags allowed' => ['', '<p><b>Text</b><br /><hr/></p>', '<p><b>Text</b><br><hr></p>'],
            'specified tags retained' => ['<b><br>', '<p><b>Text</b><i>Italic</i><br /><hr/></p>', '<b>Text</b>Italic<br>'],
            'empty content' => ['<b>', '', ''],
            'plain text' => ['<b>', 'Plain text', 'Plain text'],
            'tag list safely quoted in PHP' => ["<b>'", '<p><b>Text</b></p>', '<b>Text</b>'],
        ];
    }

    public function testPresetsHaveValidConfigurationsAndRemainEditable(): void
    {
        $type = new WysiwygEditorFieldType();
        foreach (WysiwygEditorPresets::getAll() as $handle => $preset) {
            self::assertSame([], $type->validate([
                'customConfig' => $preset['customConfig'],
                'allowedTags' => $preset['allowedTags'],
            ]));
            if ($handle !== 'default') {
                $configuration = json_decode($preset['customConfig'], true, flags: JSON_THROW_ON_ERROR);
                self::assertSame(['Unlink'], end($configuration['toolbar'])['items']);
            }
        }
        $field = WysiwygEditorFieldType::createDtoFromArray([
            'allowedTags' => '<p>',
            'customConfig' => '{"toolbar":[]}',
        ]);
        self::assertSame('<p>', $field->allowedTags);
        self::assertSame('{"toolbar":[]}', $field->customConfig);
        self::assertSame('', WysiwygEditorFieldType::createDtoFromArray([])->allowedTags);
    }

    public function testGeneratedJsonPersistsEditorSettingsWithoutPresetControl(): void
    {
        $field = [
            'fieldType' => 'wysiwyg_editor',
            'handle' => 'content',
            'label' => 'Content',
            'allowedTags' => '<p><strong>',
            'customConfig' => '{"toolbar":["Bold"]}',
        ];
        $config = $this->createBlockConfigDtoFactory()->fromGenerationArray([
            'blockHandle' => 'editor_example',
            'basic' => [$field],
            'entries' => [$field],
        ]);
        $files = $this->getService(\BlockBuilder\BlockGenerator\FileGenerator\ConfigBbJson\ConfigBbJsonFileGenerator::class)
            ->generate($this->createGenerationContext($config));
        $data = json_decode($files[0]->contents, true, flags: JSON_THROW_ON_ERROR);
        foreach (['basic', 'entries'] as $collection) {
            self::assertSame($field['allowedTags'], $data[$collection][0]['allowedTags']);
            self::assertSame($field['customConfig'], $data[$collection][0]['customConfig']);
            self::assertArrayNotHasKey('editorPreset', $data[$collection][0]);
            self::assertArrayNotHasKey('loadPreset', $data[$collection][0]);
        }
    }
}
