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

use Zend\Mvc\MvcEvent;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class ServiceC
{
    /**
     * @param MvcEvent $mvcEvent
     */
    public function init(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();
        $router = $mvcEvent->getRouter();
        // Some complicated bootstrapping using e.g. the service manager and the router
    }
}