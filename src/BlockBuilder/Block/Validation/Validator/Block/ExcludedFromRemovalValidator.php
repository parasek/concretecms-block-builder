<?php

declare(strict_types=1);

namespace BlockBuilder\Block\Validation\Validator\Block;

use BlockBuilder\Block\Validation\ValidatorInterface;
use BlockBuilder\Block\Validation\ValidationFeedback;
use BlockBuilder\Environment\EnvironmentService;
use BlockBuilder\NavigationTab\Enum\NavigationTabEnum;
use Symfony\Component\HttpFoundation\FileBag;

class ExcludedFromRemovalValidator implements ValidatorInterface
{
    public function validate(array $data, ?FileBag $files = null): ValidationFeedback
    {
        $errors = [];

        if (!empty($data['excludedFromRemoval'])) {
            $forbiddenItems = [
                'auto.css',
                FILENAME_BLOCK_ADD,
                FILENAME_BLOCK_EDIT,
                'auto.js',
                FILENAME_BLOCK_COMPOSER,
                EnvironmentService::CONFIG_BB_JSON,
                FILENAME_BLOCK_CONTROLLER,
                FILENAME_BLOCK_DB,
                FILENAME_FORM,
                FILENAME_BLOCK_ICON,
                FILENAME_BLOCK_VIEW_SCRAPBOOK,
                FILENAME_BLOCK_VIEW,
            ];
            $items = preg_split('/\R/u', (string) $data['excludedFromRemoval']) ?: [];

            foreach ($items as $item) {
                $item = trim($item);
                if ($item === '') {
                    continue;
                }

                if (
                    $item === '.'
                    || $item === '..'
                    || str_contains($item, '/')
                    || str_contains($item, '\\')
                    || preg_match('/[\x00-\x1F\x7F]/', $item) === 1
                ) {
                    $errors[] = t(
                        'The exclusion "%s" must be a file or folder name, not a path (%s).',
                        $item,
                        NavigationTabEnum::CustomCode->getName(),
                    );
                    break;
                }

                if (in_array($item, $forbiddenItems, true)) {
                    $errors[] = t('You cannot exclude "%s" (%s).', $item, NavigationTabEnum::CustomCode->getName());
                    break;
                }
            }
        }

        return new ValidationFeedback(
            errors: $errors,
            fieldsWithError: $errors === [] ? [] : ['excludedFromRemoval'],
            tabsWithError: $errors === [] ? [] : [NavigationTabEnum::CustomCode->getHandle()],
        );
    }
}
