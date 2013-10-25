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

use ZendDiCompiler\DiFactory;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class ExampleDiFactory extends DiFactory
{
    /**
     * Custom factory method with runtime parameters.
     *
     * Only runtime parameters are passed, RuntimeB's other dependencies are
     * retrieved using ZendDiCompiler.
     *
     * @param string $runtimeParam1
     * @param int $runtimeParam2
     * @return RuntimeB
     */
    public function createRuntimeB($runtimeParam1, $runtimeParam2)
    {
        $config = $this->zendDiCompiler->get('Zend\Config\Config');
        $serviceA = $this->zendDiCompiler->get('ZendDiCompiler\Example\ServiceA');
        return new RuntimeB($config, $serviceA, $runtimeParam1, $runtimeParam2);
    }
}