<?php
/**
 * DiWrapper
 *
 * This source file is part of the DiWrapper package
 *
 * @package    DiWrapper
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace DiWrapper;

/**
 * @package    DiWrapper
 */
interface SharedInstanceProviderInterface
{
    /**
     * Returns shared instances as an array ($className => $object)
     *
     * @return array
     */
    public function getSharedInstances();
}