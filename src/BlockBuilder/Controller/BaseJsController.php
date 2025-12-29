<?php

declare(strict_types=1);

namespace BlockBuilder\Controller;

use Concrete\Core\Controller\Controller;
use Concrete\Core\Http\ResponseFactoryInterface;
use Concrete\Core\Permission\Checker as Permissions;
use Concrete\Core\Page\Page;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class BaseJsController extends Controller
{
    protected function jsonSuccess(string $message = '', int $code = 200, array $additionalData = []): JsonResponse
    {
        return $this->app->make(ResponseFactoryInterface::class)->json(array_merge([
            'status' => 'success',
            'message' => $message,
            'code' => $code,
        ], $additionalData), $code);
    }

    protected function jsonError(string $message = '', int $code = 400, array $additionalData = []): JsonResponse
    {
        return $this->app->make(ResponseFactoryInterface::class)->json(array_merge([
            'status' => 'error',
            'message' => $message ?: t('Oops! Something went wrong...'),
            'code' => $code,
        ], $additionalData), $code);
    }

    protected function validateRequestMethod(): ?JsonResponse
    {
        if (!$this->request->isMethod('POST')) {
            return $this->jsonError(t('Invalid request method.'), 405);
        }

        return null;
    }

    protected function validateCsrfToken(string $tokenAction = 'csrf_token', string $tokenValue = ''): ?JsonResponse
    {
        $token = $this->app->make('token');
        if (!$token->validate($tokenAction, $tokenValue ?: $this->post('csrfToken'))) {
            return $this->jsonError(t('Invalid CSRF token. Please refresh and try again.'));
        }

        return null;
    }

    protected function validateReqdestMethod(string $tokenAction = 'csrf_token', string $tokenValue = ''): ?JsonResponse
    {
        // 1. Check Permissions (Homepage edit as proxy for admin access)
        $p = new Permissions(Page::getByID(1));
        if (!$p->canWrite()) {
            return $this->jsonError(t('Access Denied.'), 401);
        }

        // 3. Check CSRF Token


        return null; // All good
    }
}
