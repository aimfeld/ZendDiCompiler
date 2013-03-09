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
     * @param array $params
     */
    public function __construct(Config $config, array $params = array())
    {
        $this->config = $config;
        $this->param = $params;
    }
}