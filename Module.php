<?php
/**
 * DiWrapper
 *
 * This source file is part of the DiWrapper package
 *
 * @package    DiWrapper
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace DiWrapper;

use Zend\Mvc\MvcEvent;
use Zend\Config\Config;

/**
 * @package    DiWrapper
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
    public function getAutoloaderConfig()
    {
        return array('Zend\Loader\StandardAutoloader' => array('namespaces' => array(
            __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
        )));
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    /**
     * @return array
     */
    public function getServiceConfig()
    {
        // Instance must be created here already but is set up later.
        $this->diWrapper = new DiWrapper;

        return array(
            // Set diWrapper as fallback. Now Zend\ServiceManager uses DiWrapper to retrieve instances.
            'abstract_factories' => array(
                $this->diWrapper,
            ),
            // Provide di-wrapper as a Zend\ServiceManager service.
            'services' => array(
                'di-wrapper' => $this->diWrapper,
            )
        );
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $config = $mvcEvent->getApplication()->getServiceManager()->get('config');
        $this->diWrapper->setConfig(new Config($config));
        $this->diWrapper->init($mvcEvent);
    }
}