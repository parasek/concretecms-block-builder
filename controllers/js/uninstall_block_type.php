<?php

namespace Concrete\Package\BlockBuilder\Controller\Js;

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\Controller\BaseJsController;
use Symfony\Component\HttpFoundation\JsonResponse;

defined('C5_EXECUTE') or exit('Access Denied.');

class UninstallBlockType extends BaseJsController
{
    public function process(): JsonResponse
    {
        $blockTypeService = $this->app->make(BlockTypeService::class);

        if ($requestMethodResponse = $this->validateRequestMethod()) {
            return $requestMethodResponse;
        }

        if ($csrfTokenResponse = $this->validateCsrfToken()) {
            return $csrfTokenResponse;
        }

        $handle = (string) $this->post('handle');
        $blockTypeId = $blockTypeService->getBlockTypeId($handle);

        $error = $blockTypeService->validateBlockTypeBeforeUninstall($blockTypeId);
        if ($error) {
            return $this->jsonError($error);
        }

        $blockTypeName = $blockTypeService->uninstallBlockType($blockTypeId);

        return $this->jsonSuccess(t('Block "%s" has been successfully uninstalled.', $blockTypeName));
    }
}
