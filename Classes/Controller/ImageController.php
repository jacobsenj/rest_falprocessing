<?php
namespace JensJacobsen\RestFalprocessing\Controller;

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

use JensJacobsen\RestFalprocessing\Domain\Model\Dto\ImageDemand;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration;
use TYPO3\CMS\Extbase\Property\TypeConverter\PersistentObjectConverter;

/**
 * Class ImageController
 *
 * @package JensJacobsen\RestFalprocessing\Controller;
 */
class ImageController extends \JensJacobsen\RestFalprocessing\Controller\AbstractController
{
    /**
     * fileRepository
     *
     * @var \TYPO3\CMS\Core\Resource\FileRepository
     * @inject
     */
    protected $fileRepository = null;


    /**
     *
     */
    public function initializeAction()
    {
        $this->fileRepository = $this->objectManager->get(FileRepository::class);
    }


    /**
     *
     */
    public function initializeResizeAction()
    {
        if ($this->arguments->hasArgument('demand')) {
            /** @var PropertyMappingConfiguration $demandConfiguration */
            $demandConfiguration = $this->arguments['demand']->getPropertyMappingConfiguration();
            $demandConfiguration->allowAllProperties()->setTypeConverterOption(PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_CREATION_ALLOWED, true);
        }
    }


    /**
     * @param ImageDemand|NULL $demand
     * @return string
     */
    public function resizeAction(ImageDemand $demand = null)
    {
        $file = $this->fileRepository->findByUid($demand->getUid());

        if ($file instanceof File) {
            if ($demand->getWidth() > 0 || $demand->getHeight() > 0) {
                $publicUrl = $this->getPublicImageUrl($file, $demand->getWidth(), $demand->getHeight());
            } else {
                $publicUrl = $this->getPublicImageUrl($file);
            }
            debug($publicUrl);
            exit;
            if (!empty($publicUrl)) {
                return $this->returnData(array('publicUrl' => $publicUrl));
            }
        }
        return false;
    }
}
