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

use Laminas\Config\Config;

/**
 * @package    ZendDiCompiler
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