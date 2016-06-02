<?php
namespace JensJacobsen\RestFalprocessing\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2015 Jens Jacobsen <typo3@jens-jacobsen.de>
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

use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\Exception\RequiredArgumentMissingException;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Class AbstractController
 *
 * @package JensJacobsen\RestFalprocessing\Controller;
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController
{
    const EXT_KEY = 'rest_falprocessing';

    /**
     * @var \JensJacobsen\RestFalprocessing\Rest\DataProvider
     * @inject
     */
    protected $dataProvider = null;

    /**
     * @var array
     */
    protected $extConf = array();

    /**
     * @var string
     */
    protected $pathToWkhtmltopdf = '';

    /**
     * @var string
     */
    protected $pathToConvert = '';

    /**
     * @var string
     */
    protected $pathToComposite = '';

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;


    /**
     * Injects the advertisingmedium repository
     *
     * @param \JensJacobsen\RestFalprocessing\Rest\DataProvider $dataProvider
     * @return void
     */
    public function injectDataProvider(\JensJacobsen\RestFalprocessing\Rest\DataProvider $dataProvider)
    {
        $this->dataProvider = $dataProvider;
    }


    /**
     * Maps arguments delivered by the request object to the local controller arguments.
     *
     * @throws RequiredArgumentMissingException
     * @return void
     */
    protected function mapRequestArgumentsToControllerArguments()
    {
        if (method_exists($this->getRequest(), 'getSentData')) {
            $sentData = $this->getRequest()->getSentData();
            /** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
            if (is_array($sentData)) {
                foreach ($this->arguments as $argument) {
                    $argumentName = $argument->getName();
                    if ($sentData[$argumentName]) {
                        foreach ($sentData[$argumentName] as $key => &$value) {
                            if (is_array($value)) {
                                $value = implode(',', $value);
                            }
                        }
                        $argument->setValue($sentData[$argumentName]);
                    }
                }
            } else {
                foreach ($this->arguments as $argument) {
                    $argumentName = $argument->getName();
                    if (GeneralUtility::_GP($argumentName)) {
                        $argument->setValue(GeneralUtility::_GP($argumentName));
                    }
                }
            }
        } else {
            parent::mapRequestArgumentsToControllerArguments();
        }
    }


    /**
     * Calls the specified action method and passes the arguments.
     *
     * If the action returns a string, it is appended to the content in the
     * response object. If the action doesn't return anything and a valid
     * view exists, the view is rendered automatically.
     *
     * @return void
     * @api
     */
    protected function callActionMethod($arguments = null)
    {
        if (method_exists($this->getRequest(), 'getSentData')) {
            $preparedArguments = array();
            /** @var \TYPO3\CMS\Extbase\Mvc\Controller\Argument $argument */
            foreach ($this->arguments as $argument) {
                $preparedArguments[] = $argument->getValue();
            }
            if (count($preparedArguments)) {
                $actionResult = call_user_func_array(array($this, $this->actionMethodName), $preparedArguments);
            } elseif ($arguments !== null) {
                $actionResult = call_user_func_array(array($this, $this->actionMethodName), $arguments);
            } else {
                $actionResult = call_user_func(array($this, $this->actionMethodName));
            }
            return $actionResult;
        } else {
            parent::callActionMethod();
        }
    }


    /**
     * @param array $array
     * @return mixed
     */
    protected function removeDotsFromKey(array $array)
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $value = $this->removeDotsFromKey($value);
            }
            if (strstr($key, '.')) {
                $array[rtrim($key, '.')] = $value;
                unset($array[$key]);
            }
        }
        return $array;
    }


    /**
     * @throws \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException
     */
    protected function getSettings()
    {
        $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        $settings = $settings['plugin.']['tx_falprocessing.']['settings.'];
        $settings = $this->removeDotsFromKey($settings);
        return $settings;
    }


    /**
     * Handles a request. The result output is returned by altering the given response.
     *
     * @param string $action
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
     * @return void
     */
    public function processAction($action)
    {
        $this->objectManager        = GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->configurationManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Configuration\ConfigurationManager::class);
        $this->settings             = $this->getSettings();
        $this->reflectionService    = $this->objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $this->arguments            = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Controller\Arguments::class);
        $this->dataProvider         = $this->objectManager->get(\JensJacobsen\RestFalprocessing\Rest\DataProvider::class);
        $this->uriBuilder           = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::class);
        $this->actionMethodName     = $action;
        $this->extConf              = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['fal_processing']);
        if (count(func_get_args()) == 1) {
            $this->initializeActionMethodArguments();
        }
        $this->initializeAction();
        $actionInitializationMethodName = 'initialize' . ucfirst($this->actionMethodName);
        if (method_exists($this, $actionInitializationMethodName)) {
            call_user_func(array($this, $actionInitializationMethodName));
        }
        $this->mapRequestArgumentsToControllerArguments();
        if (func_get_args() > 1) {
            $args = func_get_args();
            unset($args[0]);
            return $this->callActionMethod($args);
        }
        return $this->callActionMethod();
    }


    /**
     * @param string $text
     * @return string
     */
    protected function parseRteText($text)
    {
        $htmlParser = $this->objectManager->get(RteHtmlParser::class);
        /* @var RteHtmlParser $htmlParser */
        $text = $htmlParser->TS_transform_rte($htmlParser->TS_links_rte($text));
        $text = str_replace(' rtekeep="1"', '', $text);
        return $text;
    }


    /**
     * @param \Cundd\Rest\Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }


    /**
     * @return \Cundd\Rest\Request
     */
    public function getRequest()
    {
        return $this->request;
    }


    /**
     * @param File $file
     * @param string $width
     * @param string $height
     * @param bool $crop
     * @return ProcessedFile
     */
    protected function getProcessedImage(File $file, $width = null, $height = null, $crop = false)
    {
        $imageParameters = array();
        if (!empty($width)) {
            $imageParameters['width'] = $width . ($crop ? 'c' : '');
        }
        if (!empty($height)) {
            $imageParameters['height'] = $height . ($crop ? 'c' : '');
        }
        return $file->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageParameters);
    }


    /**
     * @param File $file
     * @param string $width
     * @param string $height
     * @param bool $crop
     * @return array|bool
     */
    protected function getPublicImageUrl(File $file, $width = null, $height = null, $crop = false)
    {
        if ($file->getExtension() === 'svg' || (is_null($width) && is_null($height))) {
            return GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $file->getPublicUrl();
        }
        $processed = $this->getProcessedImage($file, $width, $height, $crop);
        if ($processed instanceof ProcessedFile
            && strstr($processed->getIdentifier(), '_processed_')
        ) {
            return GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $processed->getPublicUrl();
        }
        return false;
    }


    /**
     * @param File $file
     * @param string $width
     * @param string $height
     * @param bool $crop
     * @return array|bool
     */
    protected function getPublicImageUrlWithoutDomain(File $file, $width = null, $height = null, $crop = false)
    {
        if ($file->getExtension() === 'svg' || (is_null($width) && is_null($height))) {
            return $file->getPublicUrl();
        }
        $processed = $this->getProcessedImage($file, $width, $height, $crop);
        if ($processed instanceof ProcessedFile
            && strstr($processed->getIdentifier(), '_processed_')
        ) {
            return $processed->getPublicUrl();
        }
        return false;
    }


    /**
     * @param string $key
     * @param array $arguments
     * @return string
     */
    protected function translate($key, $arguments = null)
    {
        return trim(preg_replace('~\s+~', ' ', LocalizationUtility::translate($key, self::EXT_KEY, $arguments)));
    }


    /**
     * @param $data
     * @return array
     */
    public function returnData($data)
    {
        return array('data' => $data);
    }
}
