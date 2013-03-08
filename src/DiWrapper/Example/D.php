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
class D
{
    /**
     * @param Config $config
     * @param A $a
     * @param $param1
     * @param $param2
     */
    public function __construct(Config $config, A $a, $param1, $param2)
    {
        $this->config = $config;
        $this->a = $a;
        $this->param1 = $param1;
        $this->param2 = $param2;
    }
}