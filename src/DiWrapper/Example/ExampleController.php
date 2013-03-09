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
     * @param Config $config
     * @param ServiceA $serviceA
     */
    public function __construct(DiWrapper $diWrapper, Config $config,
                                ServiceA $serviceA)
    {
        $this->diWrapper = $diWrapper;
        $this->config = $config;
        $this->serviceA = $serviceA;
    }

    public function indexAction()
    {
        // Of course we could also contructor-inject ServiceC
        /** @var ServiceC $serviceC */
        $serviceC = $this->diWrapper->get('DiWrapper\Example\ServiceC');
        $serviceC->serviceMethod();
    }
}
