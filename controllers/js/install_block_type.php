<?php

namespace Concrete\Package\BlockBuilder\Controller\Js;

use BlockBuilder\Controller\BaseJsController;
use Concrete\Core\Block\BlockType\BlockType;
use Symfony\Component\HttpFoundation\JsonResponse;

defined('C5_EXECUTE') or exit('Access Denied.');

class InstallBlockType extends BaseJsController
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

        $bt = BlockType::installBlockType($handle);

        return $this->jsonSuccess(t('Block "%s" has been successfully installed.', $bt->getBlockTypeName()));
    }
}
