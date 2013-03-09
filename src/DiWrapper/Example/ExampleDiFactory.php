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

use DiWrapper\DiFactory;
use Zend\Config\Config;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class ExampleDiFactory extends DiFactory
{
    /**
     * Custom factory method with runtime params.
     *
     * Only runtime parameters are passed, RuntimeB's other dependencies are
     * retrieved using DiWrapper.
     *
     * @param string $runtimeParam1
     * @param int $runtimeParam2
     * @return RuntimeB
     */
    public function createRuntimeB($runtimeParam1, $runtimeParam2)
    {
        $config = $this->diWrapper->get('Zend\Config\Config');
        $serviceA = $this->diWrapper->get('DiWrapper\Example\ServiceA');
        return new RuntimeB($config, $serviceA, $runtimeParam1, $runtimeParam2);
    }
}