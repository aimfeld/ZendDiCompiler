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

use Laminas\Mvc\Controller\AbstractActionController;
use ZendDiCompiler\ZendDiCompiler;
use Laminas\Config\Config;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class ExampleController extends AbstractActionController
{
    /**
     * @param ZendDiCompiler $zendDiCompiler
     * @param ServiceA $serviceA
     * @param ServiceC $serviceC
     * @param Config $config
     */
    public function __construct(ZendDiCompiler $zendDiCompiler, ServiceA $serviceA,
                                ServiceC $serviceC, Config $config)
    {
        $this->zendDiCompiler = $zendDiCompiler;
        $this->serviceA = $serviceA;
        $this->serviceC = $serviceC;
        $this->config = $config;
    }
}
