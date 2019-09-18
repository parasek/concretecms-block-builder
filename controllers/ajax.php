<?php namespace Concrete\Package\BlockBuilder\Controller;

use Concrete\Core\Controller\Controller;
use Concrete\Core\Validation\CSRF\Token;
use Concrete\Core\File\Service\File as FileService;
use Concrete\Core\Page\Page;
use Concrete\Core\Permission\Checker as Permissions;
use Concrete\Core\User\User;

defined('C5_EXECUTE') or die('Access Denied.');

class Ajax extends Controller
{

    public function deleteBlockTypeFolder() {

        $response = [];

        // Check simple permissions
        $u = new User();

        $p = new Permissions(Page::getByID(1)); // Check if user can edit Homepage

        if ( !$u->IsLoggedIn() OR !$p->canWrite()) {

            $response['status']  = 'error';
            $response['code']    = 400;
            $response['message'] = t('Oops! Something went wrong...');

            echo $this->app->make('helper/json')->encode($response);
            exit();

        }

        // Permit only $_POST actions
        if ( ! $this->post()) {

            $response['status']  = 'error';
            $response['code']    = 400;
            $response['message'] = t('Oops! Something went wrong...');

            echo $this->app->make('helper/json')->encode($response);
            exit();

        }

        // Check against CSRF attacks
        $token = new Token();

        if ( ! $token->validate('ajax_csrf_token', $this->post('ajaxCsrfToken'))) {

            $response['status']  = 'error';
            $response['code']    = 400;
            $response['message'] = t('Oops! Something went wrong...');

            echo $this->app->make('helper/json')->encode($response);
            exit();

        }

        // Delete folder
        $blockBuilderValidation = $this->app->build('BlockBuilder\Validation');

        if ($blockBuilderValidation->isBlockInstalled($this->post('handle'))) {

            $response['status']  = 'error';
            $response['code']    = 400;
            $response['message'] = t('Uninstall block type before deleting folder.');
            echo $this->app->make('helper/json')->encode($response);
            exit;

        }

        $blockTypePath = DIR_FILES_BLOCK_TYPES . DIRECTORY_SEPARATOR . $this->post('handle');
        $fileService = new FileService();

        if (!is_dir($blockTypePath) OR !$fileService->removeAll($blockTypePath, true)) {
            $response['status']  = 'error';
            $response['code']    = 400;
            $response['message'] = t('Block type folder couldn\'t be deleted.');
            echo $this->app->make('helper/json')->encode($response);
            exit;
        }

        // Return success message
        $response['status']  = 'success';
        $response['code']    = 200;
        $response['message'] = t('Block type folder has been deleted.');
        echo $this->app->make('helper/json')->encode($response);
        exit;

    }

}