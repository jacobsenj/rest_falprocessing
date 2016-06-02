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

use Cundd\Rest\DataProvider\Utility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyLoadingProxy;
use TYPO3\CMS\Extbase\Persistence\Generic\LazyObjectStorage;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

/**
 * Class DataProvider
 *
 * @package JensJacobsen\RestFalprocessing\Rest
 */
class DataProvider extends \Cundd\Rest\DataProvider\DataProvider
{
    /**
     * Returns the domain model controller class name for the given API path
     *
     * @param string $path API path to get the repository for
     * @return string
     */
    public function getControllerClassForPath($path)
    {
        list($vendor, $extension, $model) = Utility::getClassNamePartsForPath($path, FALSE);
        $repositoryClass = 'Tx_' . $extension . '_Controller_' . $model . 'Controller';
        if (!class_exists($repositoryClass)) {
            $repositoryClass = ($vendor ? $vendor . '\\' : '') . $extension . '\\Controller\\' . $model . 'Controller';
        }
        return $repositoryClass;
    }


    /**
     * Returns the data from the given model
     *
     * @param \TYPO3\CMS\Extbase\DomainObject\DomainObjectInterface|object $model
     * @return array<mixed>
     */
    public function getModelData($model)
    {
        $doNotAddClass = (bool)$this->objectManager->getConfigurationProvider()->getSetting('doNotAddClass', 0);
        $properties    = null;
        if (is_object($model)) {
            // Get the data from the model
            if (method_exists($model, 'jsonSerialize')) {
                $properties = $model->jsonSerialize();
            } else {
                if ($model instanceof FileReference) {
                    $properties = array(
                        'publicUrl'   => GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . $model->getOriginalResource()->getPublicUrl(),
                        'name'        => $model->getOriginalResource()->getName(),
                        'title'       => $model->getOriginalResource()->getTitle(),
                        'description' => $model->getOriginalResource()->getDescription(),
                        'alternative' => $model->getOriginalResource()->getAlternative(),
                        'mimeType'    => $model->getOriginalResource()->getMimeType(),
                        'uid'         => (int)$model->getOriginalResource()->getReferenceProperty('uid_local'),
                    );
                } elseif ($model instanceof DomainObjectInterface) {
                    $properties = $model->_getProperties();
                } else {
                    if ($model instanceof ObjectStorage) {
                        $properties    = array_values(iterator_to_array($model));
                        $doNotAddClass = true;
                    }
                }
            }

            // Transform objects recursive
            if (is_array($properties)) {
                foreach ($properties as $propertyKey => $propertyValue) {
                    if (is_object($propertyValue)) {
                        if ($propertyValue instanceof LazyLoadingProxy) {
                            $properties[$propertyKey] = $this->getModelDataFromLazyLoadingProxy($propertyValue,
                                $propertyKey, $model);
                        } else {
                            if ($propertyValue instanceof LazyObjectStorage) {
                                $properties[$propertyKey] = $this->getModelDataFromLazyObjectStorage($propertyValue,
                                    $propertyKey, $model);
                            } else {
                                $properties[$propertyKey] = $this->getModelData($propertyValue);
                            }
                        }
                    }
                }
            }

            if (!$doNotAddClass && $properties && !isset($properties['__class'])) {
                $properties['__class'] = get_class($model);
            }
        }

        if (!$properties) {
            $properties = $model;
        }
        return $properties;
    }
}
