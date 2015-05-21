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

use Zend\Di\Di;
use Zend\Di\Definition\ArrayDefinition;
use Zend\Di\Definition\CompilerDefinition;
use Zend\Di\Definition\IntrospectionStrategy;
use Zend\Di\DefinitionList;
use Zend\Di\InstanceManager;
use Zend\Code\Scanner\DirectoryScanner;
use Zend\Config\Config;
use Zend\Di\Config as DiConfig;
use Zend\Log\Logger;
use Zend\Log\Writer\Stream as StreamWriter;
use Zend\Mvc\MvcEvent;
use Zend\EventManager\GlobalEventManager;
use DateTime;
use ZendDiCompiler\Exception\RuntimeException;
use ZendDiCompiler\Exception\RecoverException;

/**
 * ZendDiCompiler
 *
 * @package    ZendDiCompiler
 */
class ZendDiCompiler
{
    const TEMP_SERVICE_LOCATOR = 'TempServiceLocator';

    /**
     * Global instance
     *
     * @var ZendDiCompiler
     */
    protected static $zendDiCompiler = null;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var object[]
     */
    protected $sharedInstances = array();

    /**
     * @var string[]
     */
    protected $typePreferences = array();

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var bool
     */
    protected $hasBeenReset = false;

    /**
     * @var GeneratedServiceLocator|TempServiceLocator
     */
    protected $generatedServiceLocator = null;

    /**
     * @var IntrospectionStrategy
     */
    protected $introspectionStrategy = null;

    /**
     * @return ZendDiCompiler
     */
    public static function getGlobalInstance()
    {
        if (!self::$zendDiCompiler) {
            throw new RuntimeException('ZendDiCompiler must be initialized before the global instance can be retrieved.');
        }
        return self::$zendDiCompiler;
    }

    /**
     * Add shared instances to be used by ZendDiCompiler.
     *
     * Typical things you want to add are e.g. a db adapter, the config, a session. These instances are
     * then constructor-injected by ZendDiCompiler. Call this e.g. in the onBootstrap() method of your module class.
     *
     * @param array $sharedInstances ('MyModule\MyClass' => $instance)
     *
     * @throws RuntimeException
     */
    public function addSharedInstances(array $sharedInstances)
    {
        // Instances added after initialization (e.g. in onBootstrap() calls) have to be passed to the service locator
        if ($this->isInitialized) {
            throw new RuntimeException(
                'Shared instances must be added before ZendDiCompiler is initialized.
                Make sure your module is added *before* the ZendDiCompiler module.'
            );
        }

        $this->sharedInstances = array_merge($this->sharedInstances, $sharedInstances);
    }

    /**
     * Use this to replace the standard introspection strategy of ZendDiCompiler
     *
     * Call this the onBootstrap() method of your module class.
     *
     * @param IntrospectionStrategy $introspectionStrategy
     */
    public function setIntrospectionStrategy(IntrospectionStrategy $introspectionStrategy)
    {
        $this->introspectionStrategy = $introspectionStrategy;
    }

    /**
     * @param string $name        The full class name (including namespace)
     * @param array  $params      A parameter array passed to the class constructor, if it has a array $params argument
     * @param bool   $newInstance If true, create a new instance every time (use as factory)
     *
     * @throws \Exception
     * @return null|object
     */
    public function get($name, array $params = array(), $newInstance = false)
    {
        $this->checkInit();

        $name = $this->getTypePreference($name);

        $instance = null;

        try {
            // Convert PHP errors to exception to catch errors due to changed constructors, etc.
            set_error_handler(array($this, 'exceptionErrorHandler'));

            // Outdated definition may cause exceptions (e.g. constructor changes) or a null instance (e.g. new class added).
            /** @noinspection PhpUndefinedMethodInspection */
            $instance = $this->generatedServiceLocator->get($name, $params, $newInstance);

            // Handle null instance
            if (!$instance && !$this->hasBeenReset) {
                $this->generatedServiceLocator = $this->reset(true);
                /** @noinspection PhpUndefinedMethodInspection */
                $instance = $this->generatedServiceLocator->get($name, $params, $newInstance);
            }
            restore_error_handler();
        } catch (RecoverException $e) {
            restore_error_handler();

            // Oops, maybe the class constructor has changed during development? Try with rescanned DI definitions.
            if (!$this->hasBeenReset) {
                $this->generatedServiceLocator = $this->reset(true);
                /** @noinspection PhpUndefinedMethodInspection */
                $instance = $this->generatedServiceLocator->get($name, $params, $newInstance);
            }

        } catch (\Exception $e) {
            restore_error_handler();
            throw $e;
        }

        if (!$instance) {
            $message = sprintf("Unable to create an instance for class '%s'.", $name);
            throw new RuntimeException($message);
        }

        return $instance;
    }

    /**
     * Is called by the ZendDiCompiler module itself.
     *
     * @param Config $config
     */
    public function setConfig(Config $config)
    {
        $this->config = $config;

        // Provide easy access to type preferences
        /** @var Config $typePreferences */
        $typePreferences       = $this->config->get('di')->instance->preference;
        $this->typePreferences = $typePreferences->toArray();
    }


    /**
     * Set up DI definitions and create instances.
     *
     * All shared instances must be added before this function is called!
     *
     * Is called by the ZendDiCompiler module itself.
     */
    public function init()
    {
        if ($this->isInitialized) {
            throw new RuntimeException('ZendDiCompiler has been initialized already');
        }

        $this->addDefaultSharedInstances();

        $this->isInitialized = true;

        $zdcConfig = $this->config->get('zendDiCompiler');
        $fileName  = realpath(sprintf('%s/%s.php', $zdcConfig->writePath, $zdcConfig->serviceLocatorClass));
        if (file_exists($fileName)) {
            require_once $fileName;
            $serviceLocatorClass           = __NAMESPACE__ . '\\' . $zdcConfig->serviceLocatorClass;
            $this->generatedServiceLocator = new $serviceLocatorClass;
            $this->setSharedInstances($this->generatedServiceLocator, $this->sharedInstances);
        } else {
            $this->generatedServiceLocator = $this->reset(false);
        }

        self::$zendDiCompiler = $this;
    }

    /**
     * Returns class name or its substitute.
     *
     * @param string $className
     *
     * @return string
     */
    public function getTypePreference($className)
    {
        return array_key_exists($className, $this->typePreferences) ?
            $this->typePreferences[$className] : $className;
    }

    /**
     * Shared instances which can be injected if Zend\Mvc is used.
     *
     * @param MvcEvent $mvcEvent
     */
    public function addMvcSharedInstances(MvcEvent $mvcEvent)
    {
        $application    = $mvcEvent->getApplication();
        $serviceManager = $application->getServiceManager();

        $mvcSharedInstances = array(
            'Zend\Mvc\MvcEvent'                           => $mvcEvent,
            'Zend\Mvc\ApplicationInterface'               => $application,
            'Zend\ServiceManager\ServiceLocatorInterface' => $serviceManager,
            'Zend\EventManager\EventManagerInterface'     => GlobalEventManager::getEventCollection(),
            'Zend\Mvc\Router\RouteStackInterface'         => $mvcEvent->getRouter(),
            'Zend\View\Renderer\PhpRenderer'              => $serviceManager->get('Zend\View\Renderer\PhpRenderer'),
        );

        $this->addSharedInstances($mvcSharedInstances);
    }

    /**
     * Default shared instances which can be injected
     */
    protected function addDefaultSharedInstances()
    {
        // Provide merged config as shared instance
        $defaultSharedInstances = array(
            'Zend\Config\Config'       => $this->config, // The merged global configuration
            'ZendDiCompiler\DiFactory' => new DiFactory($this), // Provide DiFactory
            get_class($this)           => $this, // Provide ZendDiCompiler itself
        );
        $this->addSharedInstances($defaultSharedInstances);
    }

    /**
     * @return DefinitionList
     */
    protected function getDefinitionList()
    {
        // Compiling definitions can take a while.
        set_time_limit(1000);

        // Set up the directory scanner.
        $directoryScanner = new DirectoryScanner;
        foreach ($this->config->get('zendDiCompiler')->scanDirectories as $directory) {
            $directoryScanner->addDirectory($directory);
        }

        // Compile definitions and convert them to an array.
        $introspectionStrategy = $this->getIntrospectionStrategy();
        $compilerDefinition    = new CompilerDefinition($introspectionStrategy);
        $compilerDefinition->setAllowReflectionExceptions(true);
        $compilerDefinition->addDirectoryScanner($directoryScanner);
        $compilerDefinition->compile();
        $definitionArray = $compilerDefinition->toArrayDefinition()->toArray();

        // Set up the DIC with compiled definitions.
        $definitionList     = new DefinitionList(array());
        $compiledDefinition = new ArrayDefinition($definitionArray);
        $definitionList->addDefinition($compiledDefinition);

        return $definitionList;
    }

    /**
     * Get introspection strategy (use only constructor injection by default)
     *
     * @return IntrospectionStrategy
     */
    protected function getIntrospectionStrategy()
    {
        if (!$this->introspectionStrategy) {
            $this->introspectionStrategy = new IntrospectionStrategy();

            // Interface injection is not supported
            $this->introspectionStrategy->setInterfaceInjectionInclusionPatterns(array());

            // Setter injection is not supported
            $this->introspectionStrategy->setMethodNameInclusionPatterns(array());
        }

        return $this->introspectionStrategy;
    }

    /**
     * @param InstanceManager|GeneratedServiceLocator|TempServiceLocator $object
     * @param array                                                      $sharedInstances
     */
    protected function setSharedInstances($object, array $sharedInstances)
    {
        /** @noinspection PhpUndefinedClassInspection */
        assert(
            $object instanceof InstanceManager ||
            $object instanceof GeneratedServiceLocator ||
            $object instanceof TempServiceLocator
        );

        if ($object instanceof InstanceManager) {
            foreach ($sharedInstances as $classOrAlias => $instance) {
                $object->addSharedInstance($instance, $classOrAlias);
            }
        } else {
            foreach ($sharedInstances as $classOrAlias => $instance) {
                /** @noinspection PhpUndefinedMethodInspection */
                $object->set($classOrAlias, $instance);
            }
        }
    }

    /**
     * @param bool $recoverFromOutdatedDefinitions
     *
     * @throws Exception\RuntimeException
     * @return GeneratedServiceLocator|TempServiceLocator
     */
    protected function generateServiceLocator($recoverFromOutdatedDefinitions)
    {
        // Check if write directory exists and create it if not
        $this->prepareWritePath();

        // Setup Di
        $di       = new Di;
        $diConfig = new DiConfig($this->config->get('di'));


        // Setup scan logging
        $zdcConfig    = $this->config->get('zendDiCompiler');
        $logFile      = $zdcConfig->writePath . '/' . $zdcConfig->scanLogFileName;
        $this->logger = new Logger;
        $this->logger->addWriter(new StreamWriter($logFile, 'w'));

        $di->configure($diConfig);
        $definitionList = $this->getDefinitionList();
        $this->writeComponentDependencyInfo($definitionList);
        $di->setDefinitionList($definitionList);

        $this->setSharedInstances($di->instanceManager(), $this->sharedInstances);

        $generator = new Generator($di, $this->config, $this->logger);

        list($fileName, $generatedClass) = $this->writeServiceLocator($generator, $zdcConfig->serviceLocatorClass);

        if ($recoverFromOutdatedDefinitions) {
            // Within a php request, a class can only be loaded once. Therefore, we need a
            // temporary service locator class with updated definitions. The class
            // is used to create the service locator and then immediately deleted afterwards.
            // The next request uses the updated service locator class.
            list($fileName, $generatedClass) = $this->writeServiceLocator($generator, $zdcConfig->tempServiceLocatorClass);
            require_once $fileName;
            /** @var TempServiceLocator $serviceLocator */
            $serviceLocator = new $generatedClass;

            // Reuse previous shared instances.
            /** @noinspection PhpUndefinedFieldInspection */
            $serviceLocator->services = $this->generatedServiceLocator->services;
            unlink($fileName);
        } else {
            require_once $fileName;
            $serviceLocator = new $generatedClass;
        }

        return $serviceLocator;
    }

    /**
     * @param Generator $generator
     * @param string    $className Without namespace
     *
     * @return string[] Filename and full class name of the generated service locator class
     */
    protected function writeServiceLocator(Generator $generator, $className)
    {
        $generator->setNamespace(__NAMESPACE__);
        $generator->setContainerClass($className);

        $this->logger->info('Start generating service locator by code scanning.');
        $file = $generator->getCodeGenerator();
        $this->logger->info('Code scanning finished.');

        $path     = $this->config->get('zendDiCompiler')->writePath;
        $fileName = $path . "/$className.php";
        $file->setFilename($fileName);
        $this->logger->info(sprintf('Writing generated service locator to %s.', $fileName));
        $file->write();
        $this->logger->info(sprintf('Finished writing generated service locator to %s.', $fileName));

        return array($fileName, __NAMESPACE__ . '\\' . $className);
    }

    /**
     * Write component dependencies
     *
     * Scanned classes are grouped into components. The second part of the class name
     * is the component name (Zend\Db\Adapter\Adapter => Db component). The output
     * shows for every component on which other components it depends by analysing
     * class names of injected classes.
     *
     * @param DefinitionList $definitions
     */
    protected function writeComponentDependencyInfo(DefinitionList $definitions)
    {
        try {
            $classes = $definitions->getClasses();

            // Group classes into components
            $components = array();
            foreach ($classes as $className) {
                $componentName = $this->getComponentName($className);
                if ($componentName === false) {
                    continue;
                }
                $constructorParams = $definitions->getMethodParameters($className, '__construct');
                foreach ($constructorParams as $constructorParam) {
                    // Key 1 contains the full class name for typed parameters.
                    $paramClassName = isset($constructorParam[1]) ? $constructorParam[1] : null;
                    if (isset($paramClassName) && // array params excluded
                        (!array_key_exists($componentName, $components) ||
                            !in_array($paramClassName, $components[$componentName]))
                    ) {
                        $components[$componentName][] = $paramClassName;
                    }
                }
            }

            // Sort components alphabetically
            ksort($components);

            // Generate output
            $now  = (new DateTime('now'))->format('Y-m-d H:i:s');
            $info = sprintf('Component dependency info - generated by %s (%s)', __NAMESPACE__, $now) . PHP_EOL;
            $info .= PHP_EOL;
            $info .= 'Scanned classes are grouped into components (e.g. the Zend\Mvc\MvcEvent class belongs to the Zend\Mvc component).' . PHP_EOL .
                'For every component, all constructor-injected classes are listed. This helps you analyze which components' . PHP_EOL .
                'depend on which classes of other components. Consider organizing your components into layers.' . PHP_EOL .
                'Each layer should depend on classes of the same or lower layers only.' . PHP_EOL .
                'Note that only constructor-injection is considered for this analysis, so the picture might be incomplete.' . PHP_EOL;
            $info .= PHP_EOL;

            foreach ($components as $componentName => $dependencies) {
                if (count($dependencies) == 0) {
                    continue;
                }
                sort($dependencies);
                $info .= "$componentName classes inject:" . PHP_EOL;
                foreach ($dependencies as $dependency) {
                    $info .= "- $dependency" . PHP_EOL;
                }
                $info .= PHP_EOL;
            }
        } catch (\Exception $e) {
            $info = $e->getMessage() . PHP_EOL . $e->getTraceAsString();
        }

        $zendDiCompilerConfig = $this->config->get('zendDiCompiler');
        $fileName             = $zendDiCompilerConfig->writePath . '/' . $zendDiCompilerConfig->componentDependencyFileName;
        file_put_contents($fileName, $info);
    }

    /**
     * @param string $className
     *
     * @return bool|string Return false if component name could not be determined.
     */
    protected function getComponentName($className)
    {
        $separator = '\\';
        $pos1      = strpos($className, $separator);
        if (!$pos1) {
            $separator = '_'; // Try classnames without namespace
            $pos1      = strpos($className, $separator);
            if (!$pos1) {
                return false;
            }
        }
        $pos2          = strpos($className, $separator, $pos1 + 1);
        $componentName = $pos2 ? substr($className, 0, $pos2) : substr($className, 0, $pos1);
        return $componentName;
    }

    /**
     * Scan code and update the GeneratedServiceLocator class.
     *
     * @param bool $recoverFromOutdatedDefinitions
     *
     * @return GeneratedServiceLocator|TempServiceLocator
     */
    protected function reset($recoverFromOutdatedDefinitions)
    {
        $generatedServiceLocator = $this->generateServiceLocator($recoverFromOutdatedDefinitions);
        $this->setSharedInstances($generatedServiceLocator, $this->sharedInstances);

        $this->hasBeenReset = true;

        return $generatedServiceLocator;
    }

    /**
     * @throws RuntimeException
     */
    protected function checkInit()
    {
        if (!$this->isInitialized) {
            throw new RuntimeException(
                sprintf(
                    "ZendDiCompiler must be initialized before instances can be retrieved. If you don't use Zend\\Mvc, override 'useZendMvc' in the zendDiCompiler config.", get_class($this)
                )
            );
        }
    }

    /**
     * Convert PHP errors to exceptions.
     *
     * Instantiating classes with wrong constructor arguments results in an
     * E_RECOVERABLE_ERROR which is converted in a RecoverException.
     *
     * @see http://php.net/manual/en/class.errorexception.php
     *
     * @param $errno
     * @param $errstr
     * @param $errfile
     * @param $errline
     *
     * @return bool
     * @throws RecoverException
     */
    public function exceptionErrorHandler($errno, $errstr, $errfile, $errline)
    {
        if ($errno == E_RECOVERABLE_ERROR) {
            throw new RecoverException($errstr, 0, $errno, $errfile, $errline);
        }
        return;
    }

    /**
     * @throws RuntimeException
     */
    protected function prepareWritePath()
    {
        $path = $this->config->get('zendDiCompiler')->writePath;
        if (!file_exists($path) && !is_dir($path) && !mkdir($path)) {
            throw new RuntimeException(sprintf('The directory %s could not be created, check write permissions.', $path));
        } elseif (!is_writable($path)) {
            throw new RuntimeException(sprintf('The directory %s is not writable.', $path));
        }
    }
}