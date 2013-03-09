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

use Zend\Mvc\Controller\AbstractActionController;
use DiWrapper\DiWrapper;
use Zend\Config\Config;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class ExampleController extends AbstractActionController
{
    /**
     * @param DiWrapper $diWrapper
     * @param ServiceA $serviceA
     * @param ServiceC $serviceC
     * @param Config $config
     */
    public function __construct(DiWrapper $diWrapper, ServiceA $serviceA,
                                ServiceC $serviceC, Config $config)
    {
        $this->diWrapper = $diWrapper;
        $this->serviceA = $serviceA;
        $this->serviceC = $serviceC;
        $this->config = $config;
    }
}
