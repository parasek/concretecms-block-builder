<?php

namespace Concrete\Package\BlockBuilder\Controller\Js;

use BlockBuilder\Block\Service\BlockTypeService;
use BlockBuilder\Controller\BaseJsController;
use Symfony\Component\HttpFoundation\JsonResponse;

defined('C5_EXECUTE') or exit('Access Denied.');

class DeleteBlockTypeFolder extends BaseJsController
{
    public function process(): JsonResponse
    {
        if ($requestMethodResponse = $this->validateRequestMethod()) {
            return $requestMethodResponse;
        }

        if ($csrfTokenResponse = $this->validateCsrfToken()) {
            return $csrfTokenResponse;
        }

        $handle = (string) $this->post('handle');
        $blockTypeService = $this->app->make(BlockTypeService::class);

        $error = $blockTypeService->validateBlockTypeFolderBeforeDeletion($handle);
        if ($error) {
            return $this->jsonError($error);
        }

        $result = $blockTypeService->deleteBlockTypeFolder($handle);
        if ($result !== true) {
            return $this->jsonError($result);
        }

        return $this->jsonSuccess(t('Block folder has been deleted.'));
    }
}
