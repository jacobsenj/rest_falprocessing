<?php
namespace JensJacobsen\RestFalprocessing\Rest;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2015 Jens Jacobsen <typo3@jens-jacobsen.de>
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use Bullet\App;
use Bullet\Response;
use Cundd\Rest\Dispatcher;
use Cundd\Rest\HandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

// composer autoloading
require_once realpath(PATH_site . ('../vendor')) . '/autoload.php';

/**
 * Class Handler
 *
 * @package JensJacobsen\RestFalprocessing\Rest
 */
class Handler extends \Cundd\Rest\Handler implements HandlerInterface
{
    /**
     * Returns the Data Provider
     *
     * @return DataProvider
     */
    protected function getDataProvider()
    {
        return $this->objectManager->getDataProvider();
    }


    /**
     * Returns a response with the given message and status code
     *
     * @param string|array $data Data to send
     * @param int $status Status code of the response
     * @param bool $forceError If TRUE the response will be treated as an error, otherwise any status below 400 will be a normal response
     * @return Response
     * @internal
     */
    public function createResponse($data, $status, $forceError = false)
    {
        $body     = null;
        $response = new Response(null, $status);
        $format   = $this->getRequest()->format();
        if (!$format) {
            $format = 'json';
        }

        $messageKey = 'message';
        if ($forceError || $status >= 400) {
            $messageKey = 'error';
        }

        switch ($format) {
            case 'json':

                switch (gettype($data)) {
                    case 'string':
                        $body = array(
                            $messageKey => $data
                        );
                        break;

                    case 'array':
                        $body = $data;
                        break;

                    case 'NULL':
                        $body = array(
                            $messageKey => $response->statusText($status)
                        );
                        break;
                }

                $response->contentType('application/json');
                $response->content(json_encode($body));
                break;

            default:
                $body = sprintf('Unsupported format: %s. Please set the Accept header to application/json', $format);
                $response->content($body);
        }
        return $response;
    }


    /**
     * Returns the data of the current Model
     *
     * @return array|integer Returns the Model's data on success, otherwise a descriptive error code
     */
    public function show()
    {
        $dispatcher   = Dispatcher::getSharedDispatcher();
        $dataProvider = $this->getDataProvider();
        $controller   = $dataProvider->getControllerClassForPath($this->getPath());
        $action       = $this->getIdentifier() . 'Action';
        $controller   = GeneralUtility::makeInstance($controller);

        if (is_object($controller)) {
            $controller->setRequest($dispatcher->getRequest());
        }
        if (is_numeric($this->getIdentifier())
            && is_object($controller) && method_exists($controller, 'showAction')
        ) {
            $result = $controller->processAction('showAction', $this->getIdentifier());
        } elseif (is_object($controller) && method_exists($controller, $action)) {
            $result = $controller->processAction($action);
        } else {
            $result = false;
        }

        return $result ? $this->createResponse($result, 200) : $result;
    }


    public function create()
    {
        $dispatcher   = Dispatcher::getSharedDispatcher();
        $dataProvider = $this->getDataProvider();
        $controller   = $dataProvider->getControllerClassForPath($this->getPath());
        $controller   = GeneralUtility::makeInstance($controller);
        if (is_object($controller)) {
            $controller->setRequest($dispatcher->getRequest());
        }
        if (is_object($controller) && method_exists($controller, 'createAction')) {
            $result = $controller->processAction('createAction');
        } else {
            $result = false;
        }
        return $result;
    }


    public function replace()
    {
        return false;
    }


    public function update()
    {
        return false;
    }


    public function options()
    {
        return '';
    }


    public function delete()
    {
        $dispatcher   = Dispatcher::getSharedDispatcher();
        $dataProvider = $this->getDataProvider();
        $controller   = $dataProvider->getControllerClassForPath($this->getPath());
        $controller   = GeneralUtility::makeInstance($controller);
        if (is_object($controller)) {
            $controller->setRequest($dispatcher->getRequest());
        }
        if (is_numeric($this->getIdentifier()) && is_object($controller) && method_exists($controller, 'deleteAction')) {
            $result = $controller->processAction('deleteAction', $this->getIdentifier());
        } else {
            $result = false;
        }
        return $result;
    }


    /**
     * Configure the API paths
     */
    public function configureApiPaths()
    {
        $dispatcher = Dispatcher::getSharedDispatcher();

        /** @var App $app */
        $app = $dispatcher->getApp();

        /** @var HandlerInterface */
        $handler = $this;


        $app->path($dispatcher->getPath(), function ($request) use ($handler, $app) {
            $handler->setRequest($request);

            /*
             * Handle a specific Model
             */
            $app->param('slug', function ($request, $identifier) use ($handler, $app) {
                $handler->setIdentifier($identifier);

                /*
                 * Get single property
                 */
                $getPropertyCallback = function ($request, $propertyKey) use ($handler) {
                    return $handler->getProperty($propertyKey);
                };
                $app->param('slug', $getPropertyCallback);

                /*
                 * Show a single Model
                 */
                $getCallback = function ($request) use ($handler) {
                    return $handler->show();
                };
                $app->get($getCallback);

                /*
                 * Replace a Model
                 */
                $replaceCallback = function ($request) use ($handler) {
                    return $handler->replace();
                };
                $app->put($replaceCallback);
                $app->post($replaceCallback);

                /*
                 * Update a Model
                 */
                $updateCallback = function ($request) use ($handler) {
                    return $handler->update();
                };
                $app->patch($updateCallback);

                /*
                 * Delete a Model
                 */
                $deleteCallback = function ($request) use ($handler) {
                    return $handler->delete();
                };
                $app->delete($deleteCallback);

                /*
                 * Options
                 */
                $optionsCallBack = function ($request) use ($handler) {
                    return $handler->options();
                };
                $app->options($optionsCallBack);
            });

            /*
             * Create a Model
             */
            $createCallback = function ($request) use ($handler) {
                return $handler->create();
            };
            $app->post($createCallback);

            /*
             * List all Models
             */
            $listCallback = function ($request) use ($handler) {
                return $handler->listAll();
            };
            $app->get($listCallback);
        });
    }
}
