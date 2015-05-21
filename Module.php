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
use Zend\EventManager\EventInterface as Event;
use Zend\ModuleManager\ModuleManager;
use Zend\ServiceManager\ServiceManager;

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
     * @var Config
     */
    protected $config;

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
            // Provide ZendDiCompiler as a Zend\ServiceManager service.
            'services' => array(
                'ZendDiCompiler' => $this->zendDiCompiler,
            )
        );
    }

    /**
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager)
    {
        // Remember to keep the init() method as lightweight as possible
        $events = $moduleManager->getEventManager();
        $events->attach('loadModules.post', array($this, 'modulesLoaded'));
    }

    /**
     * This method is called once all modules are loaded.
     *
     * @param Event $e
     */
    public function modulesLoaded(Event $e)
    {
        // This method is called once all modules are loaded and the config has been merged.
        /** @var ServiceManager $serviceManager */
        $serviceManager = $e->getParam('ServiceManager');
        $this->config = new Config($serviceManager->get('config'));
        $this->zendDiCompiler->setConfig($this->config);

        // If Zend\Mvc is not used, the onBootstrap event won't be called and no mvc-related
        // shared instances will be added.
        if (!$this->config->zendDiCompiler->useZendMvc) {
            $this->zendDiCompiler->init();
        };
    }

    /**
     * If Zend\Mvc is used, this function will be called and mvc-related shared instances will be provided
     *
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $this->zendDiCompiler->addMvcSharedInstances($mvcEvent);

        if ($this->config->zendDiCompiler->useZendMvc) {
            $this->zendDiCompiler->init();
        };
    }
}