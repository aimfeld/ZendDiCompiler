_!!!Warning: this module is still in alpha stage, use at your own risk!!!_

# DiWrapper

DiWrapper is a Zend Framework 2 module that uses auto-generated factory code for dependency-injection. 
It saves you a lot of work, since there's no need anymore for writing Zend\ServiceManager factory closures 
and keeping them up-to-date manually.

DiWrapper scans your code (using Zend\Di) and creates factory methods automatically. If the factory methods are outdated, DiWrapper
updates them in the background. Therefore, you develop faster and performance in production is great, too!

## Features

- DI definition scanning and factory code generation
- Can deal with shared instances and type preferences
- Can be used as a fallback abstract factory for Zend\ServiceManager, just like Zend\Di\Di
- Detection of outdated generated code and automatic rescanning (great for development)
- Can create new instances or reuse instances created before

## Current limitations

- Only constructor-injection supported (but e.g. not setter-injection)

# Installation

This module is available on [Packagist](https://packagist.org/packages/aimfeld/di-wrapper).
In your project's `composer.json` use:

    {   
        "require": {
            "aimfeld/di-wrapper": "0.1.*"
    }
    
Make sure that the data directory is writable, e.g.

    chmod 775 vendor/aimfeld/di-wrapper/data
    
Finally, add `DiWrapper` to the modules array in your `application.config.php`. DiWrapper must be the loaded _after_ the
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
            $b = $diWrapper->get('DiWrapper\Example\B');
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


