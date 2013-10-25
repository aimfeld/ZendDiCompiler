<?php
/**
 * ZendDiCompiler
 *
 * This source file is part of the ZendDiCompiler package
 *
 * @package    ZendDiCompiler
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace ZendDiCompiler;

use Zend\Mvc\MvcEvent;
use Zend\Config\Config;

/**
 * @package    ZendDiCompiler
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
        $this->zendDiCompiler = new ZendDiCompiler;

        return array(
            // Set zendDiCompiler as fallback. Now Zend\ServiceManager uses ZendDiCompiler to retrieve instances.
            'abstract_factories' => array(
                $this->zendDiCompiler,
            ),
            // Provide ZendDiCompiler as a Zend\ServiceManager service.
            'services' => array(
                'ZendDiCompiler' => $this->zendDiCompiler,
            )
        );
    }

    /**
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $config = $mvcEvent->getApplication()->getServiceManager()->get('config');
        $this->zendDiCompiler->setConfig(new Config($config));
        $this->zendDiCompiler->init($mvcEvent);
    }
}