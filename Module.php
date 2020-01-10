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

use Laminas\Mvc\MvcEvent;
use Laminas\Config\Config;
use Laminas\EventManager\EventInterface as Event;
use Laminas\ModuleManager\ModuleManager;
use Laminas\ServiceManager\ServiceManager;

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
        return [
            'Laminas\Loader\StandardAutoloader' => [
                'namespaces' => [
            __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ]
            ]
        ];
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

        return [
            // Provide ZendDiCompiler as a Laminas\ServiceManager service.
            'services' => [
                'ZendDiCompiler' => $this->zendDiCompiler,
            ]
        ];
    }

    /**
     * @param ModuleManager $moduleManager
     */
    public function init(ModuleManager $moduleManager)
    {
        // Remember to keep the init() method as lightweight as possible
        $events = $moduleManager->getEventManager();
        $events->attach('loadModules.post', [$this, 'modulesLoaded']);
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

        // If Laminas\Mvc is not used, the onBootstrap event won't be called and no mvc-related
        // shared instances will be added.
        if (!$this->config->get('zendDiCompiler')->useZendMvc) {
            $this->zendDiCompiler->init();
        }
    }

    /**
     * If Laminas\Mvc is used, this function will be called and mvc-related shared instances will be provided
     *
     * @param MvcEvent $mvcEvent
     */
    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $this->zendDiCompiler->addMvcSharedInstances($mvcEvent);

        if ($this->config->zendDiCompiler->useZendMvc) {
            $this->zendDiCompiler->init();
        }
    }
}