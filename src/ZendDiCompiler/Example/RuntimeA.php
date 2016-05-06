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

use Zend\Config\Config;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class RuntimeA
{
    /**
     * @param Config $config
     * @param ServiceA $serviceA
     * @param array $zdcParams
     */
    public function __construct(Config $config, ServiceA $serviceA,
        array $zdcParams = []
    )
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->params = $zdcParams;
    }
}