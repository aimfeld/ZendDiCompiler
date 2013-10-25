<?php
/**
 * ZendDiCompiler
 *
 * This source file is part of the ZendDiCompiler package
 *
 * @package    ZendDiCompiler
 * @subpackage Example
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace ZendDiCompiler\Example;

/**
 * @package    ZendDiCompiler
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