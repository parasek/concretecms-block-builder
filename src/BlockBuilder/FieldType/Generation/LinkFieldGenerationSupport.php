<?php

declare(strict_types=1);

namespace BlockBuilder\FieldType\Generation;

use BlockBuilder\BlockGenerator\Generation\Plan\BlockGenerationPlanBuilder;
use BlockBuilder\BlockGenerator\Generation\Plan\CodeFragment;
use BlockBuilder\BlockGenerator\Generation\Plan\ControllerUseStatement;
use BlockBuilder\BlockGenerator\Generation\Plan\Enum\ControllerMethodSectionEnum;
use Concrete\Core\File\File;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Response\FileResponse;
use Concrete\Core\Permission\Response\PageResponse;
use Concrete\Core\Permission\Response\Response as PermissionResponse;

/**
 * Shared persisted-data and generated-controller conventions for link field types.
 */
final readonly class LinkFieldGenerationSupport
{
    public const string NORMALIZE_METHOD = 'normalizeBlockBuilderLinkData';
    public const string ENCODE_METHOD = 'encodeBlockBuilderLinkData';
    public const string RESOLVE_METHOD = 'resolveBlockBuilderLink';
    public const string VALIDATE_METHOD = 'getBlockBuilderLinkValidationError';

    public const string TYPE_SITEMAP = 'link_from_sitemap';
    public const string TYPE_FILE = 'link_from_file_manager';
    public const string TYPE_EXTERNAL = 'external_link';

    /**
     * Adds methods shared by Flex Link and the individual link field types.
     */
    public function contributeControllerCode(BlockGenerationPlanBuilder $planBuilder): void
    {
        $planBuilder->controller
            ->addUseStatement(new ControllerUseStatement(File::class))
            ->addUseStatement(new ControllerUseStatement(Page::class))
            ->addUseStatement(new ControllerUseStatement(FileResponse::class))
            ->addUseStatement(new ControllerUseStatement(PageResponse::class))
            ->addUseStatement(new ControllerUseStatement(PermissionResponse::class, 'PermissionResponse'))
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'link.shared.normalize',
                    code: $this->renderNormalizeMethod(),
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'link.shared.encode',
                    code: $this->renderEncodeMethod(),
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'link.shared.resolve',
                    code: $this->renderResolveMethod(),
                ),
            )
            ->addMethodFragment(
                ControllerMethodSectionEnum::AdditionalMethods->value,
                new CodeFragment(
                    key: 'link.shared.validate',
                    code: $this->renderValidateMethod(),
                ),
            );
    }

    private function renderNormalizeMethod(): string
    {
        return <<<'PHP'
private function normalizeBlockBuilderLinkData(mixed $value): array
{
    if (is_string($value) && trim($value) !== '') {
        $decodedValue = json_decode($value, true);
        $value = is_array($decodedValue) ? $decodedValue : [];
    }
    if (!is_array($value)) {
        $value = [];
    }

    $normalizeString = static fn(mixed $item): string => is_scalar($item)
        ? trim((string) $item)
        : '';
    $linkType = $normalizeString($value['link_type'] ?? '');
    if (!in_array($linkType, ['link_from_sitemap', 'link_from_file_manager', 'external_link'], true)) {
        $linkType = '';
    }
    $protocol = $normalizeString($value['protocol'] ?? 'https://');
    if (!in_array($protocol, ['http://', 'https://', 'BASE_URL', 'CURRENT_PAGE', 'other'], true)) {
        $protocol = 'other';
    }

    return [
        'link_type' => $linkType,
        'show_additional_fields' => !empty($value['show_additional_fields']) ? 1 : 0,
        'link_from_sitemap' => is_scalar($value['link_from_sitemap'] ?? null)
            ? max(0, (int) $value['link_from_sitemap'])
            : 0,
        'link_from_file_manager' => is_scalar($value['link_from_file_manager'] ?? null)
            ? max(0, (int) $value['link_from_file_manager'])
            : 0,
        'protocol' => $protocol,
        'external_link' => $normalizeString($value['external_link'] ?? ''),
        'ending' => $normalizeString($value['ending'] ?? ''),
        'text' => $normalizeString($value['text'] ?? ''),
        'title' => $normalizeString($value['title'] ?? ''),
        'new_window' => !empty($value['new_window']) ? 1 : 0,
        'no_follow' => !empty($value['no_follow']) ? 1 : 0,
    ];
}
PHP;
    }

    private function renderEncodeMethod(): string
    {
        return <<<'PHP'
private function encodeBlockBuilderLinkData(mixed $value): string
{
    return json_encode(
        $this->normalizeBlockBuilderLinkData($value),
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
    );
}
PHP;
    }

    private function renderResolveMethod(): string
    {
        return <<<'PHP'
private function resolveBlockBuilderLink(mixed $value): array
{
    $link = $this->normalizeBlockBuilderLinkData($value);
    $url = '';
    $object = false;
    $name = '';
    $filename = '';

    if ($link['link_type'] === 'link_from_sitemap' && $link['link_from_sitemap'] > 0) {
        $page = Page::getByID($link['link_from_sitemap']);
        if ($page && !$page->isError() && !$page->isInTrash()) {
            $object = $page;
            $url = (string) $page->getCollectionLink();
            $name = (string) $page->getCollectionName();
        }
    } elseif ($link['link_type'] === 'link_from_file_manager' && $link['link_from_file_manager'] > 0) {
        $file = File::getByID($link['link_from_file_manager']);
        $fileVersion = $file?->getApprovedVersion();
        if ($fileVersion) {
            $object = $file;
            $url = (string) $fileVersion->getURL();
            $filename = (string) $fileVersion->getFileName();
        }
    } elseif ($link['link_type'] === 'external_link' && $link['external_link'] !== '') {
        if (in_array($link['protocol'], ['http://', 'https://'], true)) {
            $url = $link['protocol'] . preg_replace('#^https?://#i', '', $link['external_link']);
        } elseif ($link['protocol'] === 'BASE_URL') {
            $url = rtrim((string) BASE_URL, '/') . '/' . ltrim($link['external_link'], '/');
        } elseif ($link['protocol'] === 'CURRENT_PAGE') {
            $currentPage = Page::getCurrentPage();
            if ($currentPage && !$currentPage->isError()) {
                $url = rtrim((string) $currentPage->getCollectionLink(), '/')
                    . '/' . ltrim($link['external_link'], '/');
            }
        } elseif ($this->isSafeBlockBuilderLinkUrl($link['external_link'])) {
            $url = $link['external_link'];
        }
    }

    $link['url'] = $url;
    $link['object'] = $object;
    $link['name'] = $name;
    $link['filename'] = $filename;

    return $link;
}

private function isSafeBlockBuilderLinkUrl(string $url): bool
{
    if ($url === '' || preg_match('/[\x00-\x1F\x7F]/', $url) === 1) {
        return false;
    }

    $scheme = parse_url($url, PHP_URL_SCHEME);

    return $scheme === null
        || in_array(strtolower($scheme), ['http', 'https', 'mailto', 'tel'], true);
}
PHP;
    }

    private function renderValidateMethod(): string
    {
        return <<<'PHP'
private function getBlockBuilderLinkValidationError(mixed $value, bool $required): ?string
{
    if (is_string($value)) {
        if (trim($value) === '') {
            return $required ? 'required' : null;
        }
        $value = json_decode($value, true);
    }
    if (!is_array($value)) {
        return 'invalid_value';
    }

    foreach ([
        'link_type',
        'show_additional_fields',
        'link_from_sitemap',
        'link_from_file_manager',
        'protocol',
        'external_link',
        'ending',
        'text',
        'title',
        'new_window',
        'no_follow',
    ] as $property) {
        if (array_key_exists($property, $value) && $value[$property] !== null && !is_scalar($value[$property])) {
            return 'invalid_value';
        }
    }
    foreach (['show_additional_fields', 'new_window', 'no_follow'] as $booleanProperty) {
        if (
            array_key_exists($booleanProperty, $value)
            && !in_array($value[$booleanProperty], [null, '', 0, 1, '0', '1', false, true], true)
        ) {
            return 'invalid_option';
        }
    }

    $linkType = isset($value['link_type']) && is_scalar($value['link_type'])
        ? trim((string) $value['link_type'])
        : '';
    if ($linkType === '') {
        return $required ? 'required' : null;
    }
    if (!in_array($linkType, ['link_from_sitemap', 'link_from_file_manager', 'external_link'], true)) {
        return 'invalid_option';
    }

    $link = $this->normalizeBlockBuilderLinkData($value);
    if ($linkType === 'link_from_sitemap') {
        $pageID = filter_var($value['link_from_sitemap'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $page = $pageID === false ? null : Page::getByID($pageID);
        if (!$page || $page->isError() || $page->isInTrash()) {
            return 'missing_destination';
        }

        /** @var PageResponse $pagePermissions */
        $pagePermissions = PermissionResponse::getResponse($page);
        if (!$pagePermissions->canViewPageInSitemap()) {
            return 'missing_destination';
        }
    }
    if ($linkType === 'link_from_file_manager') {
        $fileID = filter_var($value['link_from_file_manager'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        $file = $fileID === false ? null : File::getByID($fileID);
        if (!$file?->getApprovedVersion()) {
            return 'missing_destination';
        }

        /** @var FileResponse $filePermissions */
        $filePermissions = PermissionResponse::getResponse($file);
        if (!$filePermissions->validate('view_file_in_file_manager')) {
            return 'missing_destination';
        }
    }
    if ($linkType === 'external_link') {
        if ($link['external_link'] === '') {
            return 'missing_destination';
        }
        if (
            !isset($value['protocol'])
            || !is_scalar($value['protocol'])
            || !in_array(trim((string) $value['protocol']), ['http://', 'https://', 'BASE_URL', 'CURRENT_PAGE', 'other'], true)
        ) {
            return 'invalid_option';
        }
        if ($link['protocol'] === 'other' && !$this->isSafeBlockBuilderLinkUrl($link['external_link'])) {
            return 'unsafe_url';
        }
    }

    if (
        mb_strlen($link['external_link']) > 255
        || mb_strlen($link['ending']) > 255
        || mb_strlen($link['text']) > 255
        || mb_strlen($link['title']) > 255
    ) {
        return 'too_long';
    }

    return null;
}
PHP;
    }
}
