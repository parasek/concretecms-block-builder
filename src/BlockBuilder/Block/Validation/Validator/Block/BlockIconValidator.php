<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Service\BlockIconProvider;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Block\Validation\ValidationFeedbackBuilder;
use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Enum\BlockFormContextEnum;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use BlockBuilder\Service\Option\BlockIconOptionProvider;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

readonly class BlockIconValidator implements ValidatorInterface
{
    private const int REQUIRED_WIDTH = 97;
    private const int REQUIRED_HEIGHT = 97;
    private const int MAX_FILE_SIZE = 1_048_576;

    public function __construct(
        private BlockIconOptionProvider $blockIconOptions,
        private BlockIconProvider $blockIconProvider,
    ) {
    }

    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $feedback = new ValidationFeedbackBuilder();
        $customIcon = $files?->get('customBlockIcon');

        if ($customIcon instanceof UploadedFile) {
            $this->validateCustomIcon($feedback, $customIcon);

            return $feedback->build();
        }

        if ($customIcon !== null) {
            $feedback->addError(
                error: t(
                    'The uploaded "Custom block icon" value is invalid (%s).',
                    NavigationTabEnum::BlockSettings->getName(),
                ),
                field: 'customBlockIcon',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );

            return $feedback->build();
        }

        $this->validateSelectedIcon($feedback, $data);

        return $feedback->build();
    }

    private function validateSelectedIcon(ValidationFeedbackBuilder $feedback, array $data): void
    {
        $selectedIcon = $data['blockIcon'] ?? null;

        if ($selectedIcon === null || $selectedIcon === '') {
            return;
        }

        $blockHandle = is_scalar($data['blockHandle'] ?? null)
            ? (string) $data['blockHandle']
            : '';
        $availableIcons = $this->blockIconOptions->getOptions(BlockFormContextEnum::NewBlock, $blockHandle)
            + $this->blockIconOptions->getOptions(BlockFormContextEnum::Config, $blockHandle);
        $allowedIconPaths = array_map('strval', array_keys($availableIcons));

        if (
            is_string($selectedIcon)
            && (
                in_array($selectedIcon, $allowedIconPaths, true)
                || $this->blockIconProvider->isExistingApplicationBlockIcon($selectedIcon)
            )
        ) {
            return;
        }

        $feedback->addError(
            error: t(
                'The field "%s" contains an invalid option (%s).',
                t('Block icon'),
                NavigationTabEnum::BlockSettings->getName(),
            ),
            field: 'blockIcon',
            tab: NavigationTabEnum::BlockSettings->getHandle(),
        );
    }

    private function validateCustomIcon(ValidationFeedbackBuilder $feedback, UploadedFile $customIcon): void
    {
        if (!$customIcon->isValid()) {
            $feedback->addError(
                error: t(
                    'The uploaded "Custom block icon" file is invalid (%s).',
                    NavigationTabEnum::BlockSettings->getName(),
                ),
                field: 'customBlockIcon',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );

            return;
        }

        $fileSize = $customIcon->getSize();
        if (!is_int($fileSize) || $fileSize > self::MAX_FILE_SIZE) {
            $feedback->addError(
                error: t(
                    'The "Custom block icon" must not be larger than %s MB (%s).',
                    intdiv(self::MAX_FILE_SIZE, 1_048_576),
                    NavigationTabEnum::BlockSettings->getName(),
                ),
                field: 'customBlockIcon',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );

            return;
        }

        $imageSize = @getimagesize($customIcon->getPathname());
        if ($imageSize === false) {
            $feedback->addError(
                error: t(
                    'The uploaded "Custom block icon" is not a valid image (%s).',
                    NavigationTabEnum::BlockSettings->getName(),
                ),
                field: 'customBlockIcon',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );

            return;
        }

        if (($imageSize['mime'] ?? null) !== 'image/png') {
            $feedback->addError(
                error: t(
                    'The "Custom block icon" must be a PNG image (%s).',
                    NavigationTabEnum::BlockSettings->getName(),
                ),
                field: 'customBlockIcon',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );
        }

        $width = $imageSize[0];
        $height = $imageSize[1];
        if ($width !== self::REQUIRED_WIDTH || $height !== self::REQUIRED_HEIGHT) {
            $feedback->addError(
                error: t(
                    'The "Custom block icon" must be exactly %spx x %spx. Current size: %spx x %spx (%s).',
                    self::REQUIRED_WIDTH,
                    self::REQUIRED_HEIGHT,
                    $width,
                    $height,
                    NavigationTabEnum::BlockSettings->getName(),
                ),
                field: 'customBlockIcon',
                tab: NavigationTabEnum::BlockSettings->getHandle(),
            );
        }
    }
}
