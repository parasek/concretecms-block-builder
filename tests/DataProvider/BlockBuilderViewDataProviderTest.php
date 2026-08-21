<?php

declare(strict_types=1);

namespace BlockBuilder\Tests\DataProvider;

use BlockBuilder\DataProvider\BlockBuilderViewDataProvider;
use Concrete\Core\Localization\Localization;
use Concrete\Core\Localization\Translator\Adapter\Plain\TranslatorAdapter;
use Concrete\Core\Localization\Translator\TranslatorAdapterFactoryInterface;
use Concrete\Core\Localization\Translator\TranslatorAdapterRepository;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Test type: Block Builder form-default contract test.
 *
 * Verifies that generated-code labels retain stable English message IDs regardless of the
 * dashboard locale active when a new block configuration is created.
 */
final class BlockBuilderViewDataProviderTest extends TestCase
{
    /**
     * Confirms that an active translator does not localize the initial values used by generated t() calls.
     */
    public function testDefaultGeneratedLabelsRemainEnglishWhenTheDashboardUsesTranslations(): void
    {
        $localization = Localization::getInstance();
        $originalRepository = $localization->getTranslatorAdapterRepository();
        $translatedRepository = new TranslatorAdapterRepository(
            new class implements TranslatorAdapterFactoryInterface {
                public function createTranslatorAdapter($locale): TranslatorAdapter
                {
                    $adapter = new class extends TranslatorAdapter {
                        public function translate($text): string
                        {
                            return 'translated:' . parent::translate(...func_get_args());
                        }
                    };
                    $adapter->setLocale($locale);

                    return $adapter;
                }
            },
        );
        $localization->setTranslatorAdapterRepository($translatedRepository);

        try {
            $provider = (new ReflectionClass(BlockBuilderViewDataProvider::class))->newInstanceWithoutConstructor();
            $values = $provider->getDefaultFormValues();
        } finally {
            $localization->setTranslatorAdapterRepository($originalRepository);
        }

        foreach ($values as $key => $value) {
            if (str_ends_with($key, 'Label')) {
                self::assertFalse(
                    str_starts_with($value, 'translated:'),
                    sprintf('The default value of "%s" must remain an English translation message ID.', $key),
                );
            }
        }

        self::assertSame('Basic information', $values['basicLabel']);
        self::assertSame('Add at the top', $values['addAtTheTopLabel']);
        self::assertSame('Link from Sitemap', $values['linkFromSitemapLabel']);
        self::assertSame('No results matched {0}', $values['noResultsMatchedLabel']);
    }
}
