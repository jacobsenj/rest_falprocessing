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

use Cundd\Rest\Access\AccessControllerInterface;
use Cundd\Rest\Access\ConfigurationBasedAccessController;
use Cundd\Rest\Access\Exception\InvalidConfigurationException;

/**
 * Class AccessController
 *
 * @package JensJacobsen\RestFalprocessing\Rest
 */
class AccessController extends ConfigurationBasedAccessController
{
    /**
     * Returns the configuration matching the given request path
     *
     * @param string $path
     * @return string
     */
    public function getConfigurationForPath($path)
    {
        $configuredPaths       = $this->getConfiguredPaths();
        $matchingConfiguration = array();

        foreach ($configuredPaths as $configuration) {
            $currentPath        = $configuration['path'];
            $currentPathPattern = str_replace('*', '\w*', str_replace('?', '\w', $currentPath));
            $currentPathPattern = str_replace('/', '\/', $currentPathPattern);
            $currentPathPattern = "!^$currentPathPattern$!";
            if ($currentPath === 'all' && !$matchingConfiguration) {
                $matchingConfiguration = $configuration;
            } else {
                if (preg_match($currentPathPattern, $path)) {
                    $matchingConfiguration = $configuration;
                }
            }
        }
        return $matchingConfiguration;
    }


    /**
     * Returns the configuration matching the current request's path
     *
     * @return string
     * @throws \UnexpectedValueException if the request is not set
     */
    public function getConfigurationForCurrentPath()
    {
        if (!$this->request) {
            throw new \UnexpectedValueException('The request isn\'t set', 1376816053);
        }

        $configuration = $this->getConfigurationForPath($this->request->url());
        if (is_array($configuration)
            && array_key_exists('path', $configuration)
            && $configuration['path'] == 'all'
        ) {
            $configuration = $this->getConfigurationForPath($this->request->path());
        }
        return $configuration;
    }


    /**
     * Returns if the current request has access to the requested resource
     *
     * @throws InvalidConfigurationException if the configuration is incomplete
     * @return AccessControllerInterface::ACCESS
     */
    public function getAccess()
    {
        $configurationKey = self::ACCESS_METHOD_READ;
        $configuration    = $this->getConfigurationForCurrentPath();

        if ($this->isWrite()) {
            $configurationKey = self::ACCESS_METHOD_WRITE;
        }

        // Throw an exception if the configuration is not complete
        if (!isset($configuration[$configurationKey])) {
            throw new InvalidConfigurationException($configurationKey . ' configuration not set', 1376826223);
        }

        $access = $configuration[$configurationKey];
        if ($access === AccessControllerInterface::ACCESS_REQUIRE_LOGIN) {
            return $this->checkAuthentication();
        }
        return $access;
    }
}
