<?php
namespace JensJacobsen\RestFalprocessing\Domain\Model\Dto;

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

/**
 * Class ImageDemand
 *
 * @package JensJacobsen\RestFalprocessing\Domain\Model\Dto
 */
class ImageDemand
{
    /**
     * @var integer
     */
    protected $uid = 0;

    /**
     * @var string
     */
    protected $width = 0;

    /**
     * @var string
     */
    protected $height = 0;


    /**
     * @return integer
     */
    public function getUid()
    {
        return $this->uid;
    }


    /**
     * @param integer $uid
     */
    public function setUid($uid)
    {
        $this->uid = $uid;
    }


    /**
     * @return string
     */
    public function getWidth()
    {
        return $this->width;
    }


    /**
     * @param string $width
     */
    public function setWidth($width)
    {
        $this->width = $width;
    }


    /**
     * @return string
     */
    public function getHeight()
    {
        return $this->height;
    }


    /**
     * @param string $height
     */
    public function setHeight($height)
    {
        $this->height = $height;
    }
}
