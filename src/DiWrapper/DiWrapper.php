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

use Zend\Di\Definition\ArrayDefinition;
use Zend\Di\Definition\CompilerDefinition;
use Zend\Di\Definition\IntrospectionStrategy;
use Zend\Di\DefinitionList;
use Zend\Di\InstanceManager;
use Zend\Code\Scanner\DirectoryScanner;
use Zend\Config\Config;
use Zend\Di\Config as DiConfig;
use Zend\ServiceManager\AbstractFactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use DiWrapper\Exception\RuntimeException;


/**
 * Wrapper class for Zend\Di\Di
 *
 * Features:
 * - DI definition scanning and factory code generation
 * - Can deal with shared instances
 * - Can be used as a fallback abstract factory for Zend\ServiceManager
 * - Detects outdated generated code and automatic rescanning (great for development)
 * - Can create new instances or reuse instances created before
 *
 * @package    DiWrapper
 */
class DiWrapper implements AbstractFactoryInterface
{
    const GENERATED_SERVICE_LOCATOR = 'GeneratedServiceLocator';
    const TEMP_SERVICE_LOCATOR = 'TempServiceLocator';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var GetSharedInstancesInterface
     */
    protected $sharedInstanceGetter;

    /**
     * @var string[]
     */
    protected $typePreferences = array();

    /**
     * @var Di
     */
    protected $di;

    /**
     * @var bool
     */
    protected $isInitialized = false;

    /**
     * @var GeneratedServiceLocator|TempServiceLocator
     */
    protected $generatedServiceLocator;


    /**
     * Set up DI definitions and create instances.
     *
     * @param Config $config  Must contain a valid ZF2 DI configuration
     * @param GetSharedInstancesInterface $sharedInstanceGetter
     */
    public function init(Config $config, GetSharedInstancesInterface $sharedInstanceGetter)
    {
        $this->config = $config;
        $this->sharedInstanceGetter = $sharedInstanceGetter;

        $this->di = new Di;

        $this->applyConfig();

        $this->isInitialized = true;

        $fileName = realpath(__DIR__ . sprintf('/../../data/%s.php', self::GENERATED_SERVICE_LOCATOR));
        if (file_exists($fileName)) {
            require_once $fileName;
            $serviceLocatorClass = __NAMESPACE__ . '\\' . self::GENERATED_SERVICE_LOCATOR;
            $this->generatedServiceLocator = new $serviceLocatorClass;
            $this->setSharedInstances($this->generatedServiceLocator);
        } else {
            $this->generatedServiceLocator = $this->reset(false);
        }
    }

    /**
     * Allows for class substitution.
     *
     * @param $name
     * @param array $params
     * @param bool $newInstance
     * @return null|object
     */
    public function get($name, array $params = array(), $newInstance = false)
    {
        $this->checkInit();

        $name = $this->getTypePreference($name);

        $instance = null;
        try {
            /** @noinspection PhpUndefinedMethodInspection */
            $instance = $this->generatedServiceLocator->get($name, $params, $newInstance);
        } catch (\Exception $e) {
            // Ignore exceptions, recovery in code below
        }

        if (! $instance) {
            // Oops, maybe the class constructor has changed during development? Try with rescanned DI definitions.
            $this->generatedServiceLocator = $this->reset(true);

            // If an exception occurs here or null is returned, the problem was not caused by outdated DI definitions.
            /** @noinspection PhpUndefinedMethodInspection */
            $instance = $this->generatedServiceLocator->get($name, $params, $newInstance);
        }

        return $instance;
    }

    /**
     * Determine if we can create a service with name.
     *
     * This function is called by the ServiceManager.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return bool
     */
    public function canCreateServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return true; // Yes, we can!
    }

    /**
     * Create service with name.
     *
     * This function is called by the ServiceManager.
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @param $name
     * @param $requestedName
     * @return mixed
     */
    public function createServiceWithName(ServiceLocatorInterface $serviceLocator, $name, $requestedName)
    {
        return $this->get($requestedName);
    }

    /**
     * Returns class name or its substitute.
     *
     * @param $className
     * @return string
     */
    public function getTypePreference($className)
    {
        return array_key_exists($className, $this->typePreferences) ?
            $this->typePreferences[$className] : $className;
    }

    /**
     * Apply DI config
     */
    protected function applyConfig()
    {
        // Setup definition and instance configuration, see
        // http://framework.zend.com/manual/2.1/en/modules/zend.di.configuration.html
        $diConfig = new DiConfig($this->config->di);
        $this->di->configure($diConfig);

        // Provide easy access to type preferences
        /** @var Config $typePreferences */
        $typePreferences = $this->config->di->instance->preference;
        $this->typePreferences = $typePreferences->toArray();
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
        foreach ($this->config->di->scan_directories as $directory) {
            $directoryScanner->addDirectory($directory);
        }

        // Set up introspection strategy (use only constructor injection)
        $introspectionStrategy = new IntrospectionStrategy();
        $introspectionStrategy->setInterfaceInjectionInclusionPatterns(array());
        $introspectionStrategy->setMethodNameInclusionPatterns(array());

        // Compile definitions and convert them to an array.
        $compilerDefinition = new CompilerDefinition($introspectionStrategy);
        $compilerDefinition->setAllowReflectionExceptions(true);
        $compilerDefinition->addDirectoryScanner($directoryScanner);
        $compilerDefinition->compile();
        $definitionArray = $compilerDefinition->toArrayDefinition()->toArray();

        // Set up the DIC with compiled definitions.
        $definitionList = new DefinitionList(array());
        $compiledDefinition = new ArrayDefinition($definitionArray);
        $definitionList->addDefinition($compiledDefinition);

        return $definitionList;
    }

    /**
     * @param InstanceManager|GeneratedServiceLocator|TempServiceLocator  $object
     */
    protected function setSharedInstances($object)
    {
        /** @noinspection PhpUndefinedClassInspection */
        assert($object instanceof InstanceManager ||
            $object instanceof GeneratedServiceLocator ||
            $object instanceof TempServiceLocator);

        $sharedInstances = $this->sharedInstanceGetter->getSharedInstances($this);
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
     * @return GeneratedServiceLocator|TempServiceLocator
     */
    protected function generateServiceLocator($recoverFromOutdatedDefinitions)
    {
        $generator = new Generator(clone($this->di));

        list($fileName, $generatedClass) = $this->writeServiceLocator($generator, self::GENERATED_SERVICE_LOCATOR);

        if ($recoverFromOutdatedDefinitions) {
            // Within a php request, a class can only be loaded once. Therefore, we need a
            // temporary service locator class with updated definitions. The class
            // is used to create the service locator and then immediately deleted afterwards.
            // The next request uses the updated service locator class.
            list($fileName, $generatedClass) = $this->writeServiceLocator($generator, self::TEMP_SERVICE_LOCATOR);
            require_once $fileName;
            $serviceLocator = new $generatedClass;
            unlink($fileName);
        } else {
            require_once $fileName;
            $serviceLocator = new $generatedClass;
        }

        return $serviceLocator;
    }

    /**
     * @param Generator $generator
     * @param string $className Without namespace
     * @return string[] Filename and full class name of the generated service locator class
     */
    protected function writeServiceLocator(Generator $generator, $className)
    {
        $generator->setNamespace(__NAMESPACE__);
        $generator->setContainerClass($className);
        $file = $generator->getCodeGenerator();
        $fileName = __DIR__ . "/../../data/$className.php";
        $file->setFilename($fileName);
        $file->write();

        return array($fileName, __NAMESPACE__ . '\\' . $className);
    }


    /**
     * @param bool $recoverFromOutdatedDefinitions
     * @return GeneratedServiceLocator|TempServiceLocator
     */
    protected function reset($recoverFromOutdatedDefinitions)
    {
        $this->di->setDefinitionList($this->getDefinitionList());
        $this->setSharedInstances($this->di->instanceManager());
        $generatedServiceLocator = $this->generateServiceLocator($recoverFromOutdatedDefinitions);
        $this->setSharedInstances($generatedServiceLocator);

        return $generatedServiceLocator;
    }

    /**
     * @throws RuntimeException
     */
    protected function checkInit()
    {
        if (!$this->isInitialized) {
            throw new RuntimeException(sprintf(
                '%s:init() must be called before instances can be retrieved.', get_class($this)));
        }
    }
}