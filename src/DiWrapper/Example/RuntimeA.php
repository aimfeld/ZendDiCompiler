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
class RuntimeA
{
    /**
     * @param Config $config
     * @param ServiceA $serviceA
     * @param array $dwParams
     */
    public function __construct(Config $config, ServiceA $serviceA,
                                array $dwParams = array())
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->params = $dwParams;
    }
}