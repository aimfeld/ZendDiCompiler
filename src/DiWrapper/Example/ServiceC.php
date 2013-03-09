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

use Zend\Mvc\MvcEvent;

/**
 * @package    DiWrapper
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