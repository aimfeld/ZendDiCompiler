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
use DiWrapper\DiWrapper;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class Module
{
    /**
     * @var DiWrapper
     */
    protected $diWrapper;

    /**
     * @return array
     */
    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                // Suppose one of our routes specifies a controller named 'ExampleController'
                'ExampleController' => function() {
                    return $this->diWrapper->get('DiWrapper\Example\ExampleController');
                },
            ),
        );
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();

        // Provide DiWrapper as a local variable for convience
        $this->diWrapper = $sm->get('di-wrapper');

        // Set up shared instance
        $serviceC = new ServiceC;
        $serviceC->init($mvcEvent);

        // Provide shared instance
        $this->diWrapper->addSharedInstances(array(
            'DiWrapper\Example\ServiceC' => $serviceC,
        ));
    }
}