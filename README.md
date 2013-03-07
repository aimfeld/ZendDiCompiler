_!!!Warning: this module is still in alpha stage, use at your own risk!!!_

# DiWrapper

DiWrapper is a Zend Framework 2 module that uses auto-generated factory code for dependency-injection. 
It saves you a lot of work, since there's no need anymore for writing Zend\ServiceManager factory closures 
and keeping them up-to-date manually.

DiWrapper scans your code (using Zend\Di) and creates factory methods automatically. If the factory methods are outdated, DiWrapper
updates them in the background. Therefore, you _develop faster_, _avoid bugs_ due to outdated factory methods, and 
experience _great performance_ in production!

## Features

- DI definition scanning and factory code generation
- Can deal with shared instances and type preferences
- Is used as a fallback abstract factory for Zend\ServiceManager
- Detection of outdated generated code and automatic rescanning (great for development)
- Can create new instances or reuse instances created before
- Generates objects with runtime params using automatic service injection

## Current limitations

- Only constructor-injection supported (but e.g. not setter-injection)

# Installation

This module is available on [Packagist](https://packagist.org/packages/aimfeld/di-wrapper).
In your project's `composer.json` use:

    {   
        "require": {
            "aimfeld/di-wrapper": "0.1.*"
    }
    
Make sure have a _writable_ data folder in your application root directory, see 
[ZendSkeletonApplication](https://github.com/zendframework/ZendSkeletonApplication). Add `DiWrapper` to the 
modules array in your `application.config.php`. DiWrapper must be the loaded _after_ the
modules where it is used:

    'modules' => array(		
        'SomeModule',
        'Application',
        'DiWrapper',
    ),

# Usage

DiWrapper uses standard [Zend\Di configuration](http://framework.zend.com/manual/2.1/en/modules/zend.di.configuration.html)
(which is not well documented yet). To make things easier, see [module.config.php](https://github.com/aimfeld/di-wrapper/blob/master/config/module.config.php) for 
examples of how to specify:

- Directories for the code scanner
- Instance configuration
- Type preferences

# Example

Let's say we want to use the DiWrapper to create a controller class and inject some 
dependencies (of course without writing factory methods for Zend\ServiceManager). 
We also want to inject the DiWrapper itself into the controller, so we can use it to get 
dependencies from within the controller. We have the following classes 
(see [example source](https://github.com/aimfeld/di-wrapper/tree/master/src/DiWrapper/Example)):

ExampleController:

    namespace DiWrapper\Example;

    use Zend\Mvc\Controller\AbstractActionController;
    use DiWrapper\DiWrapper;
    use Zend\Config\Config;

    class ExampleController extends AbstractActionController
    {
        public function __construct(DiWrapper $diWrapper, Config $config, A $a)
        {
            $this->diWrapper = $diWrapper;
            $this->config = $config;
            $this->a = $a;
            
            // Of course we could also contructor-inject B, this is just for illustration
            $this->b = $diWrapper->get('DiWrapper\Example\B');
            
            // And here we use the DiWrapper as a runtime-object factory, automatically injecting the config
            $this->c = $diWrapper->get('DiWrapper\Example\C', array('hello' => 'world'), true);
        }
    }

Class A with a dependency on class B:

    namespace DiWrapper\Example;

    class A
    {
        public function __construct(B $b)
        {
            $this->b = $b;
        }
    }

Class B with a constructor parameter of unspecified type:

    class B
    {
        public function __construct($someParam)
        {
            $this->someParam = $someParam;
        }
    }
    
Class C with a runtime-parameter array passed to DiWrapper::get()
    
    class C
    {
        public function __construct(Config $config, array $params = array())
        {
            $this->config = $config;
            $this->param = $params;
        }
    }
    
We add the source directory as a scan directory for DiWrapper. Since B has a parameter of unspecified type, we
have to specify a value to inject. If class B had required the config in its constructor and retrieved the
parameter from there, we wouldn't need to specify anything. The config looks like this
(also see [module.config.php](https://github.com/aimfeld/di-wrapper/blob/master/config/module.config.php)).

    'di' => array(
        'scan_directories' => array(
            __DIR__ . '/../src/DiWrapper/Example',
        ),
        'instance' => array(
            'DiWrapper\Example\B' => array(
                'parameters' => array(
                    'someParam' => 'Hello',
                ),
            ),
        ),            
    ),

This is how you can use DiWrapper in your Application module. Here we provide the config as a shared instance to 
DiWrapper and create a controller _without writing Zend\ServiceManager factory methods for the controller, A, B, or C_:

    namespace Application;

    class Module
    {    
        protected $diWrapper;
        
        public function getControllerConfig()
        {
            return array(
                'factories' => array(
                    'Application\Controller\Example' => function() {
                        return $this->diWrapper->get('DiWrapper\Example\ExampleController');
                    },                
                ),
            );
        }    

        public function onBootstrap(MvcEvent $mvcEvent)
        {
            $sm = $mvcEvent->getApplication()->getServiceManager();

            // Add shared instances to DiWrapper
            $this->diWrapper = $sm->get('di-wrapper');
            $this->diWrapper->addSharedInstances(array(
                'Zend\Config\Config' => new Config($sm->get('config'));
            ));
        }
    }

DiWrapper has automatically generated a ServiceLocator in the [data directory](https://github.com/aimfeld/di-wrapper/tree/master/data):
Services can be created or retrieved using the get() method. 

This is all behind the scenes. You can just constructor inject stuff in you Application module and you don't need
to worry about instantiation. Some services may need to be provided as shared instances (like the config in this 
example). Just for illustration, this is the generated service locator used by DiWrapper::get(). 

    namespace DiWrapper;

    use Zend\Di\ServiceLocator;

    /**
     * Generated by DiWrapper\Generator (2013-03-07 21:11:39)
     */
    class GeneratedServiceLocator extends ServiceLocator
    {
        /**
         * @param string $name
         * @param array $params
         * @param bool $newInstance
         * @return mixed
         */
        public function get($name, array $params = array(), $newInstance = false)
        {
            if (!$newInstance && isset($this->services[$name])) {
                return $this->services[$name];
            }

            switch ($name) {
                case 'DiWrapper\Example\A':
                    return $this->getDiWrapperExampleA($params, $newInstance);

                case 'DiWrapper\Example\B':
                    return $this->getDiWrapperExampleB($params, $newInstance);
                    
                case 'DiWrapper\Example\C':
                    return $this->getDiWrapperExampleC($params, $newInstance);

                case 'DiWrapper\Example\ExampleController':
                    return $this->getDiWrapperExampleExampleController($params, $newInstance);

                default:
                    return parent::get($name, $params);
            }
        }

        /**
         * @param array $params
         * @param bool $newInstance
         * @return \DiWrapper\Example\A
         */
        public function getDiWrapperExampleA(array $params = array(), $newInstance = false)
        {
            if (!$newInstance && isset($this->services['DiWrapper\Example\A'])) {
                return $this->services['DiWrapper\Example\A'];
            }

            $object = new \DiWrapper\Example\A($this->getDiWrapperExampleB());
            if (!$newInstance) {
                $this->services['DiWrapper\Example\A'] = $object;
            }

            return $object;
        }

        /**
         * @param array $params
         * @param bool $newInstance
         * @return \DiWrapper\Example\B
         */
        public function getDiWrapperExampleB(array $params = array(), $newInstance = false)
        {
            if (!$newInstance && isset($this->services['DiWrapper\Example\B'])) {
                return $this->services['DiWrapper\Example\B'];
            }

            $object = new \DiWrapper\Example\B('Hello');
            if (!$newInstance) {
                $this->services['DiWrapper\Example\B'] = $object;
            }

            return $object;
        }
        
        /**
         * @param array $params
         * @param bool $newInstance
         * @return \DiWrapper\Example\C
         */
        public function getDiWrapperExampleC(array $params = array(), $newInstance = false)
        {
            if (!$newInstance && isset($this->services['DiWrapper\Example\C'])) {
                return $this->services['DiWrapper\Example\C'];
            }

            $object = new \DiWrapper\Example\C($this->get('Zend\Config\Config'), $params);
            if (!$newInstance) {
                $this->services['DiWrapper\Example\C'] = $object;
            }

            return $object;
        }

        /**
         * @param array $params
         * @param bool $newInstance
         * @return \DiWrapper\Example\ExampleController
         */
        public function getDiWrapperExampleExampleController(array $params = array(), $newInstance = false)
        {
            if (!$newInstance && isset($this->services['DiWrapper\Example\ExampleController'])) {
                return $this->services['DiWrapper\Example\ExampleController'];
            }

            $object = new \DiWrapper\Example\ExampleController($this->get('DiWrapper\DiWrapper'), $this->get('Zend\Config\Config'), $this->getDiWrapperExampleA());
            if (!$newInstance) {
                $this->services['DiWrapper\Example\ExampleController'] = $object;
            }

            return $object;    
        }

