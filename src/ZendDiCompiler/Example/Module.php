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

use Laminas\Mvc\MvcEvent;
use ZendDiCompiler\ZendDiCompiler;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class Module
{
    /**
     * @var ZendDiCompiler
     */
    protected $zendDiCompiler;

    /**
     * @return array
     */
    public function getControllerConfig()
    {
        return [
            'factories' => [
                // Suppose one of our routes specifies a controller named 'ExampleController'
                'ExampleController' => function() {
                    return $this->zendDiCompiler->get('ZendDiCompiler\Example\ExampleController');
                },
            ],
        ];
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();

        // Provide ZendDiCompiler as a local variable for convience
        $this->zendDiCompiler = $sm->get('ZendDiCompiler');

        // Set up shared instance
        $serviceC = new ServiceC;
        $serviceC->init($mvcEvent);

        // Provide shared instance
        $this->zendDiCompiler->addSharedInstances(
            [
            'ZendDiCompiler\Example\ServiceC' => $serviceC,
            ]
        );
    }
}