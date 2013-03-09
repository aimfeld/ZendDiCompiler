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
     * @param SharedInstance $sharedInstance
     * @param ServiceA $serviceA
     */
    public function __construct(DiWrapper $diWrapper, ServiceA $serviceA,
                                Config $config, SharedInstance $sharedInstance)
    {
        $this->diWrapper = $diWrapper;
        $this->serviceA = $serviceA;
        $this->config = $config;
        $this->sharedInstance = $sharedInstance;
    }

    public function indexAction()
    {
        // Of course we could also constructor-inject ServiceC
        $serviceC = $this->diWrapper->get('DiWrapper\Example\ServiceC');
        $serviceC->serviceMethod();
    }
}
