<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\Generation;

use BlockBuilder\Tests\Support\BlockBuilderTestCase;
use DOMDocument;
use PhpParser\Error as PhpParserError;
use PhpParser\ParserFactory;

/**
 * Test type: In-memory generated-output contract test.
 *
 * Generates every supported field type without writing files and verifies the resulting PHP,
 * XML, JSON, and template outputs are structurally valid and contain required block artifacts.
 */
final class InMemoryGenerationTest extends BlockBuilderTestCase
{
    /**
     * Verifies that every supported field type produces valid, complete block files without disk writes.
     */
    public function testAllFieldTypesGenerateValidTextFilesInMemory(): void
    {
        $config = $this->createAllFieldTypesConfig();
        $generationContext = $this->createGenerationContext($config);
        $generatedFiles = $this->generateTextFiles($generationContext);
        $filesByPath = [];
        foreach ($generatedFiles as $generatedFile) {
            $filesByPath[$generatedFile->relativePath] = $generatedFile->contents;
            self::assertDoesNotMatchRegularExpression(
                '/\{\{[A-Z][A-Z0-9_]*\}\}/',
                $generatedFile->contents,
                sprintf('Generated file "%s" contains a stub placeholder.', $generatedFile->relativePath),
            );
        }

        self::assertSame([
            'add.php',
            'auto.css',
            'auto.js',
            'composer.php',
            'config-bb.json',
            'controller.php',
            'db.xml',
            'edit.php',
            'form.php',
            'scrapbook.php',
            'view.php',
        ], array_keys($filesByPath));
        self::assertContains('app', $generationContext->plan->form->repeatableCapturedVariableNames);
        self::assertGreaterThanOrEqual(
            2,
            substr_count($filesByPath['controller.php'], '$this->set(\'app\', $this->app);'),
            'The generated controller must expose $app to both block forms and view.php.',
        );
        self::assertStringContainsString(
            'use ($app,',
            $filesByPath['form.php'],
            'The repeatable-entry renderer must capture the application service.',
        );
        self::assertStringContainsString(
            '<?php $entry_repeatableSelectField_options = isset($entry_repeatableSelectField_options) && is_array($entry_repeatableSelectField_options) ? $entry_repeatableSelectField_options : []; ?>',
            $filesByPath['form.php'],
            'Repeatable single-choice options must be initialized before the renderer captures them.',
        );
        self::assertStringContainsString(
            '<?php $entry_repeatableSelectMultipleField_options = isset($entry_repeatableSelectMultipleField_options) && is_array($entry_repeatableSelectMultipleField_options) ? $entry_repeatableSelectMultipleField_options : []; ?>',
            $filesByPath['form.php'],
            'Repeatable multiple-choice options must be initialized before the renderer captures them.',
        );
        self::assertStringContainsString('$basicSvgIconPicker_iconPreviewSource = match', $filesByPath['form.php']);
        self::assertStringContainsString('$repeatableSvgIconPicker_iconPreviewSource = match', $filesByPath['form.php']);
        self::assertStringContainsString('$basicSvgIconPicker_iconData = match', $filesByPath['view.php']);
        self::assertStringContainsString('$repeatableSvgIconPicker_iconData = match', $filesByPath['view.php']);
        self::assertStringNotContainsString('$_blockBuilderSvgIcon', $filesByPath['form.php']);
        self::assertStringNotContainsString('$_blockBuilderSvgIcon', $filesByPath['view.php']);
        self::assertStringContainsString(
            'canViewPageInSitemap()',
            $filesByPath['controller.php'],
            'Page-link validation must enforce the editor sitemap permission.',
        );
        self::assertStringContainsString(
            "validate('view_file_in_file_manager')",
            $filesByPath['controller.php'],
            'File-backed field validation must enforce the editor File Manager permission.',
        );
        self::assertStringContainsString(
            "validate('view_express_entry')",
            $filesByPath['controller.php'],
            'Express-field validation must enforce the editor entry permission.',
        );
        self::assertStringContainsString(
            'if (!$rootFolder instanceof FileFolder)',
            $filesByPath['controller.php'],
            'Files from Folder must tolerate a missing File Manager root folder.',
        );
        self::assertStringNotContainsString('DOCUMENT_ROOT', $filesByPath['view.php']);
        self::assertStringNotContainsString('var_dump(', $filesByPath['view.php']);
        self::assertStringContainsString(
            "if (formContainer.dataset.blockBuilderInitialized === 'true') {\n            // WYSIWYG initializers may be attached after the add dialog's first initialization pass.\n            initializeWysiwygEditors(formContainer);",
            $filesByPath['auto.js'],
            'An initialized add form must retry WYSIWYG setup after its editor initializers become available.',
        );

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        foreach ($filesByPath as $relativePath => $contents) {
            if (!str_ends_with($relativePath, '.php')) {
                continue;
            }
            try {
                self::assertNotNull($parser->parse($contents));
            } catch (PhpParserError $error) {
                self::fail(sprintf('Generated file "%s" is invalid PHP: %s', $relativePath, $error->getMessage()));
            }
        }

        $decodedConfig = json_decode($filesByPath['config-bb.json'], true, flags: JSON_THROW_ON_ERROR);
        self::assertSame($config->blockHandle, $decodedConfig['blockHandle']);
        self::assertCount(count($config->basic), $decodedConfig['basic']);
        self::assertCount(count($config->entries), $decodedConfig['entries']);

        $databaseDocument = new DOMDocument();
        self::assertTrue($databaseDocument->loadXML($filesByPath['db.xml']));
        self::assertSame('schema', $databaseDocument->documentElement?->localName);
        self::assertSame(
            'http://www.concrete5.org/doctrine-xml/0.5',
            $databaseDocument->documentElement?->namespaceURI,
        );
        self::assertStringContainsString(
            PHP_EOL . '        xsi:schemaLocation="http://www.concrete5.org/doctrine-xml/0.5 https://concretecms.github.io/doctrine-xml/doctrine-xml-0.5.xsd">',
            $filesByPath['db.xml'],
        );
        self::assertStringContainsString(PHP_EOL . '    <table ', $filesByPath['db.xml']);
        self::assertStringNotContainsString(PHP_EOL . '  <table ', $filesByPath['db.xml']);
        self::assertSame(2, $databaseDocument->getElementsByTagNameNS(
            'http://www.concrete5.org/doctrine-xml/0.5',
            'table',
        )->length);
    }
}
