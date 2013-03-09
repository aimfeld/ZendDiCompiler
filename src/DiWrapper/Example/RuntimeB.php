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

use Zend\Config\Config;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class RuntimeB
{
    /**
     * @param Config $config
     * @param ServiceA $serviceA
     * @param string $runtimeParam1
     * @param int $runtimeParam2
     */
    public function __construct(Config $config, ServiceA $serviceA,
                                $runtimeParam1, $runtimeParam2)
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->runtimeParam1 = $runtimeParam1;
        $this->runtimeParam2 = $runtimeParam2;
    }
}