<?php
/**
 * DiWrapper
 *
 * This source file is part of the DiWrapper package
 *
 * @package    DiWrapper
 * @subpackage Example
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace DiWrapper\Example;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class ServiceA
{
    /**
     * @param ServiceB $serviceB
     */
    public function __construct(ServiceB $serviceB)
    {
        $this->serviceB = $serviceB;
    }
}