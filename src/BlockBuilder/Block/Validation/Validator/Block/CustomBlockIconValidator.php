<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\AbstractValidator;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;

class CustomBlockIconValidator extends AbstractValidator
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $file = ($files instanceof FileBag) ? $files->get('customBlockIcon') : $files;

        if ($file instanceof UploadedFile) {
            $requiredWidth = 97;
            $requiredHeight = 97;

            if (!$file->isValid()) {
                $this->addError(
                    error: t('The uploaded "Custom block icon" file is invalid.'),
                    field: 'customBlockIcon',
                    tab: NavigationTabEnum::BlockSettings->getHandle()
                );
            } else {
                $path = $file->getPathname();
                $imageSize = @getimagesize($path);

                if (!$imageSize) {
                    $this->addError(
                        error: t('The uploaded "Custom block icon" is not a valid image.'),
                        field: 'customBlockIcon',
                        tab: NavigationTabEnum::BlockSettings->getHandle()
                    );
                } else {
                    $width = $imageSize[0];
                    $height = $imageSize[1];
                    $mime = $imageSize['mime'];

                    if ($mime !== 'image/png') {
                        $this->addError(
                            error: t('The "Custom block icon" must be a PNG image.'),
                            field: 'customBlockIcon',
                            tab: NavigationTabEnum::BlockSettings->getHandle()
                        );
                    }

                    if ($width !== $requiredWidth || $height !== $requiredHeight) {
                        $this->addError(
                            error: t(
                                'The "Custom block icon" must be exactly %spx x %spx. Current size: %spx x %spx.',
                                $requiredWidth,
                                $requiredHeight,
                                $width,
                                $height
                            ),
                            field: 'customBlockIcon',
                            tab: NavigationTabEnum::BlockSettings->getHandle()
                        );
                    }
                }
            }
        }

        return $this->getValidationFeedback();
    }
}
