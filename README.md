_This module is in beta stage. Please create github issues for bugs or feature requests._

# Table of Contents

* [Introduction](#introduction)
* [Features](#features)
* [Installation](#installation)
* [Usage](#usage)
    * [Dependency injection container vs. service locator](#dependency-injection-container-vs-service-locator)
    * [Configuration](#configuration)
    * [Shared instances](#shared-instances)
    * [Type preferences](#type-preferences)
* [Examples](#examples)
    * [Using DiWrapper to create a controller](#using-diwrapper-to-create-a-controller)
    * [Using the DiFactory to create runtime objects with dependencies](#using-the-difactory-to-create-runtime-objects-with-dependencies)
        * [Passing all runtime parameters in a $params array](#passing-all-runtime-parameters-in-a-params-array)
        * [Passing custom runtime parameters](#passing-custom-runtime-parameters)
* [The generated factory code behind the scenes](#the-generated-factory-code-behind-the-scenes)

# Introduction

Are you tired of writing tons of factory code (closures) for the `Zend\ServiceManager` in your Zend Framework 2 application?
Are outdated factory methods causing bugs? This can all be avoided by using DiWrapper!

**DiWrapper** is a Zend Framework 2 module that uses auto-generated factory code for dependency-injection.
It saves you a lot of work, since there's **no need anymore for writing
`Zend\ServiceManager` factory closures** and keeping them up-to-date manually.

DiWrapper scans your code using `Zend\Di` and creates factory methods automatically. If the factory methods are outdated, DiWrapper
updates them in the background. Therefore, you **develop faster**, **avoid bugs** due to outdated factory methods, and
experience **great performance** in production!

# Features

- **Code scanning** for creating DI definitions and **automatic factory code generation**
- Can deal with **shared instances** and **type preferences**
- Allows for **custom code introspection strategies** (by default, only constructors are scanned)
- Is automatically used as a **fallback abstract factory for `Zend\ServiceManager`**
- Can be used **instead of `Zend\ServiceManager`**
- Detection of outdated generated factory code and **automatic rescanning** (great for development)
- Can create new instances or reuse instances created before
- Can be used as a **factory for runtime objects** combining DI and passing of runtime parameters.
- **Greater perfomance** and less memory consumption, as compared to using `Zend\Di\Di` with cached definitions.

# Installation

This module is [available](https://packagist.org/packages/aimfeld/di-wrapper) on [Packagist](https://packagist.org).
In your project's `composer.json` use:

```
{
    "require": {
        "aimfeld/di-wrapper": "0.2.*"
}
```

Make sure you have a _writable_ `data` folder in your application root directory, see
[ZendSkeletonApplication](https://github.com/zendframework/ZendSkeletonApplication). Put a `.gitignore` file in it with
the following content (you may want to replace `*` with `GeneratedServiceLocator.php`):

```
*
!.gitignore
```

Add 'DiWrapper' to the modules array in your `application.config.php`. DiWrapper must be the loaded _after_ the
modules where it is used:

```php
'modules' => array(
    'SomeModule',
    'Application',
    'DiWrapper',
),
```

# Usage

## Dependency injection container vs. service locator

Is DiWrapper a _dependency injection container (DiC)_ or a _service locator (SL)_? Well, that depends on where you use it.
DiWrapper can be used as a _DiC_ to [create your controllers](#using-diwrapper-to-create-a-controller) in your module class
and inject the controller dependencies from outside. This has been coined as
[Register Resolve Release](http://blog.ploeh.dk/2010/09/29/TheRegisterResolveReleasepattern/) pattern and is
[the recommended way](http://stackoverflow.com/a/1994455/94289) by experts like Mark Seemann and others.

As soon as you inject the DiWrapper itself into your controllers and other classes, you are using it as a _service locator_.
In my opinion, it is very convenient to inject the DiWrapper as a single dependency into ZF2 controller classes which means using it as a service locator,
(just like `Zend\ServiceManager` is typically used). I think that ZF2 controllers are designed in a way that makes it difficult to respect
the [Single Responsibility Principle (SRP)](http://en.wikipedia.org/wiki/Single_responsibility_principle), that's why you'll
quickly end up with a lot of controller dependencies, even if you use controller plugins.

DiWrapper is also used as a _service locator_ inside of `DiWrapper\DiFactory` which is very useful for
[creating runtime objects with dependencies](#using-the-difactory-to-create-runtime-objects-with-dependencies). This
avoids a lot of [abstract factory code](http://stackoverflow.com/a/1945023/94289) you would otherwise have to write.
Besides ZF2 controllers, I recommend _not_ to inject DiWrapper directly anywhere. If you need a service in one of your
classes, just ask for it in the constructor. If you need to create runtime objects with dependencies, inject
DiFactory or your extended version of it with [custom creation methods](#passing-custom-runtime-parameters).


## Configuration

DiWrapper uses standard [Zend\Di configuration](http://framework.zend.com/manual/2.1/en/modules/zend.di.configuration.html)
(which is not well documented yet). To make things easier, see [example.config.php](https://github.com/aimfeld/di-wrapper/blob/master/config/example.config.php) for
examples of how to specify:

- Directories for the code scanner
- Instance configuration
- Type preferences

For a full list of configuration options, see [module.config.php](https://github.com/aimfeld/di-wrapper/blob/master/config/module.config.php)

DiWrapper creates a `GeneratedServiceLocator` class in the `data` directory and automatically refreshes it when changed constructors cause
an exception. However, if you e.g. change parameters in the [instance configuration](https://github.com/aimfeld/di-wrapper/blob/master/config/example.config.php),
you have to manually delete `data/GeneratedServiceLocator.php` to force a refresh. In your staging and production
deployment/update process, make sure that `data/GeneratedServiceLocator.php` is deleted!

## Shared instances

You need to provide shared instances to [DiWrapper::addSharedInstances()](https://github.com/aimfeld/di-wrapper/blob/master/src/DiWrapper/DiWrapper.php) in
your application module's onBootstrap() method in the following cases (also see example below):

- The object to be injected is an instance of a class outside of the [scanned directories](https://github.com/aimfeld/di-wrapper/blob/master/config/example.config.php).
- The object to be injected requires some special bootstrapping (e.g. a session object).

Note that DiWrapper provides some _default shared instances_ automatically
(see [DiWrapper::getDefaultSharedInstances()](https://github.com/aimfeld/di-wrapper/blob/master/src/DiWrapper/DiWrapper.php)).
The following _default shared instances_ can be constructor-injected without explicitly adding them:

- `DiWrapper\DiWrapper`
- `DiWrapper\DiFactory`
- `Zend\Config\Config`
- `Zend\Mvc\Router\Http\TreeRouteStack`
- `Zend\View\Renderer\PhpRenderer`

## Type preferences

It is common to inject interfaces or abstract classes. Let's have a look at interface injection (for abstract classes,
it works the same).

```php
class ServiceF
{
    public function __construct(ExampleInterface $example)
    {
        // ExampleImplementor is injected since it is a type preference for ExampleInterface
        $this->example = $example;
    }
}
```

We need to tell DiWrapper which implementing class to inject for `ExampleInterface`. We specify `ExampleImplementor` as
a type preference for `ExampleInterface` in our [example config](https://github.com/aimfeld/di-wrapper/blob/master/config/example.config.php):

```php
'di' => array(
    'instance' => array(
        'preference' => array(
            'DiWrapper\Example\ExampleInterface' => 'DiWrapper\Example\ExampleImplementor',
        ),
    ),
),
```

DiWrapper will now always inject `ExampleImplementor` for `ExampleInterface`. Calling
`DiWrapper::get('DiWrapper\Example\ExampleInterface')` will return the `ExampleImplementor`.

Type preferences can not only be used for interfaces and abstract classes, but for substituting classes
in general. They can even be used to deal with non-existing classes:

```php
'di' => array(
    'instance' => array(
        'preference' => array(
            'DiWrapper\Example\NotExists' => 'DiWrapper\Example\Exists',
        ),
    ),
),
```

Calling `DiWrapper::get('DiWrapper\Example\NotExists')` will return a `DiWrapper\Example\Exists` instance.
Believe it or not, there are actually some good use cases for this.

# Examples

All examples sources listed here are included as [source code](https://github.com/aimfeld/di-wrapper/tree/master/src/DiWrapper/Example).

## Using DiWrapper to create a controller

Let's say we want to use DiWrapper to create a controller class and inject some
dependencies. We also want to inject the DiWrapper itself into the controller, so we can use it to get
dependencies from within the controller (it is a moot topic whether this is a good idea or not).
We have the following classes:

ExampleController

```php
use Zend\Mvc\Controller\AbstractActionController;
use DiWrapper\DiWrapper;
use Zend\Config\Config;

class ExampleController extends AbstractActionController
{
    public function __construct(DiWrapper $diWrapper, ServiceA $serviceA,
                                ServiceC $serviceC, Config $config)
    {
        $this->diWrapper = $diWrapper;
        $this->serviceA = $serviceA;
        $this->serviceC = $serviceC;
        $this->config = $config;
    }
}

```

ServiceA with a dependency on ServiceB

```php
class ServiceA
{
    public function __construct(ServiceB $serviceB)
    {
        $this->serviceB = $serviceB;
    }
}
```

ServiceB with a constructor parameter of unspecified type:

```php
class ServiceB
{
    public function __construct($diParam)
    {
        $this->diParam = $diParam;
    }
}
```

ServiceC which requires complicated runtime initialization and will be added as shared instance.

```php
class ServiceC
{
    public function init(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();
        $router = $mvcEvent->getRouter();
        // Some complicated bootstrapping using e.g. the service manager and the router
    }
}
```

We add the example source directory as a scan directory for DiWrapper. Since `ServiceB` has a parameter of unspecified type, we
have to specify a value to inject. A better approach for `ServiceB` would be to require the `Config` in its constructor
and retrieve the parameter from there, so we wouldn't need to specify an instance configuration. The
[configuration](https://github.com/aimfeld/di-wrapper/blob/master/config/example.config.php) for our example
looks like this:

```php
// DiWrapper configuration
'diWrapper' => array(
    // Directories that will be code-scanned
    'scanDirectories' => array(
        // e.g. 'vendor/provider/module/src',
        __DIR__ . '/../src/DiWrapper/Example',
    ),
),
// ZF2 DI definition and instance configuration used by DiWrapper
'di' => array(
    // Instance configuration
    'instance' => array(
        // Type preferences for abstract classes and interfaces.
        'preference' => array(
            'DiWrapper\Example\ExampleInterface' => 'DiWrapper\Example\ExampleImplementor',
        ),
        // Add instance configuration if there are specific parameters to be used for instance creation.
        'DiWrapper\Example\ServiceB' => array('parameters' => array(
            'diParam' => 'Hello',
        )),
    ),
),
```

Now we can create the `ExampleController` in our application's [module class](https://github.com/aimfeld/di-wrapper/blob/master/src/DiWrapper/Example/Module.php).
For convenience, we retrieve the DiWrapper from the service manager and assign it to a local variable (`$this->diWrapper = $sm->get('di-wrapper')`).
This makes it easier for writing `getControllerConfig()` or `getViewHelperConfig()` callbacks.

Since the `ServiceC` dependency requires some complicated initialization, we need to initialize it and add it as a shared instance to
DiWrapper.

```php
class Module
{
    protected $diWrapper;

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
```

## Using the DiFactory to create runtime objects with dependencies

It is useful to distinguish two types of objects: _services_ and _runtime objects_. For _services_, all parameters should
be specified in the configuration (e.g. a config array wrapped in a `Zend\Config\Config` object). If class constructors
e.g. in third party code require some custom parameters, they can be specified in the
[instance configuration](https://github.com/aimfeld/di-wrapper/blob/master/config/example.config.php)).

_Runtime objects_, on the other hand, require at least one parameter which is determined at runtime only.
DiWrapper provides `DiWrapper\DiFactory` to help you create _runtime objects_ and inject their dependencies.

### Passing all runtime parameters in a $params array

If you follow the convention of passing runtime parameters in a single array named `$params` as in `RuntimeA`,
things are very easy (in case of parameter name clashes, the array name can be configured in
[module.config.php](https://github.com/aimfeld/di-wrapper/blob/master/config/module.config.php)):

```php
class RuntimeA
{
    public function __construct(Config $config, ServiceA $serviceA,
                                array $params = array())
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->params = $params;
    }
}
```

DiWrapper automatically injects `DiWrapper\DiFactory` as a _default shared instance_. So
we can just use it to create `RuntimeA` objects in `ServiceD`. `RuntimeA`'s dependencies (the `Config` default shared instance
and `ServiceA`) are injected automatically, so you only need to provide the runtime parameters:

```php
use DiWrapper\DiFactory;

class ServiceD
{
    public function __construct(DiFactory $diFactory)
    {
        $this->diFactory = $diFactory;
    }

    public function serviceMethod()
    {
        $runtimeA1 = $this->diFactory->create('DiWrapper\Example\RuntimeA', array('hello', 'world'));
        $runtimeA2 = $this->diFactory->create('DiWrapper\Example\RuntimeA', array('goodbye', 'world'));
    }
}
```

### Passing custom runtime parameters

If you can't or don't want to follow the convention of passing all runtime parameters in a single `$params` array,
DiWrapper still is very useful. In that case, you can just extend a custom factory from `DiWrapper\DiFactory` and
add your specific creation methods. `RuntimeB` requires two separate run time parameters:

```php
class RuntimeB
{
    public function __construct(Config $config, ServiceA $serviceA,
                                $runtimeParam1, $runtimeParam2)
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->runtimeParam1 = $runtimeParam1;
        $this->runtimeParam2 = $runtimeParam2;
    }
}
```

So we extend `ExampleDiFactory` from `DiWrapper\DiFactory` and write a creation method `createRuntimeB`:

```php
class ExampleDiFactory extends DiFactory
{
    /**
     * @param string $runtimeParam1
     * @param int $runtimeParam2
     * @return RuntimeB
     */
    public function createRuntimeB($runtimeParam1, $runtimeParam2)
    {
        $config = $this->diWrapper->get('Zend\Config\Config');
        $serviceA = $this->diWrapper->get('DiWrapper\Example\ServiceA');
        return new RuntimeB($config, $serviceA, $runtimeParam1, $runtimeParam2);
    }
}
```

In `ServiceE`, we inject our extended factory. If the extended factory is located in a directory scanned by DiWrapper,
we don't need to provide it as a shared instance. Now we can create `RuntimeB` objects as follows:

```php
class ServiceE
{
    public function __construct(ExampleDiFactory $diFactory)
    {
        $this->diFactory = $diFactory;
    }

    public function serviceMethod()
    {
        $runtimeB1 = $this->diFactory->createRuntimeB('one', 1);
        $runtimeB2 = $this->diFactory->createRuntimeB('two', 2);
    }
}
```

# The generated factory code behind the scenes

DiWrapper will automatically generate a service locator in the `data` directory and update it if constructors are changed
during development. Services can be created/retrieved using `DiWrapper::get()`. If you need a new dependency in one of your
classes, you can just put it in the constructor and DiWrapper will inject it for you.

Just for illustration, this is the generated service locator created by DiWrapper and used in `DiWrapper::get()`.

```php
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
            case 'DiWrapper\Example\ExampleController':
                return $this->getDiWrapperExampleExampleController($params, $newInstance);

            case 'DiWrapper\Example\ExampleDiFactory':
                return $this->getDiWrapperExampleExampleDiFactory($params, $newInstance);

            case 'DiWrapper\Example\RuntimeA':
                return $this->getDiWrapperExampleRuntimeA($params, $newInstance);

            case 'DiWrapper\Example\ServiceA':
                return $this->getDiWrapperExampleServiceA($params, $newInstance);

            case 'DiWrapper\Example\ServiceB':
                return $this->getDiWrapperExampleServiceB($params, $newInstance);

            case 'DiWrapper\Example\ServiceC':
                return $this->getDiWrapperExampleServiceC($params, $newInstance);

            case 'DiWrapper\Example\ServiceD':
                return $this->getDiWrapperExampleServiceD($params, $newInstance);

            default:
                return parent::get($name, $params);
        }
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

        $object = new \DiWrapper\Example\ExampleController($this->get('DiWrapper\DiWrapper'), $this->getDiWrapperExampleServiceA(), $this->getDiWrapperExampleServiceC(), $this->get('Zend\Config\Config'));
        if (!$newInstance) {
            $this->services['DiWrapper\Example\ExampleController'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \DiWrapper\Example\ExampleDiFactory
     */
    public function getDiWrapperExampleExampleDiFactory(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['DiWrapper\Example\ExampleDiFactory'])) {
            return $this->services['DiWrapper\Example\ExampleDiFactory'];
        }

        $object = new \DiWrapper\Example\ExampleDiFactory($this->get('DiWrapper\DiWrapper'));
        if (!$newInstance) {
            $this->services['DiWrapper\Example\ExampleDiFactory'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \DiWrapper\Example\RuntimeA
     */
    public function getDiWrapperExampleRuntimeA(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['DiWrapper\Example\RuntimeA'])) {
            return $this->services['DiWrapper\Example\RuntimeA'];
        }

        $object = new \DiWrapper\Example\RuntimeA($this->get('Zend\Config\Config'), $params);
        if (!$newInstance) {
            $this->services['DiWrapper\Example\RuntimeA'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \DiWrapper\Example\ServiceA
     */
    public function getDiWrapperExampleServiceA(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['DiWrapper\Example\ServiceA'])) {
            return $this->services['DiWrapper\Example\ServiceA'];
        }

        $object = new \DiWrapper\Example\ServiceA($this->getDiWrapperExampleServiceB());
        if (!$newInstance) {
            $this->services['DiWrapper\Example\ServiceA'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \DiWrapper\Example\ServiceB
     */
    public function getDiWrapperExampleServiceB(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['DiWrapper\Example\ServiceB'])) {
            return $this->services['DiWrapper\Example\ServiceB'];
        }

        $object = new \DiWrapper\Example\ServiceB('Hello');
        if (!$newInstance) {
            $this->services['DiWrapper\Example\ServiceB'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \DiWrapper\Example\ServiceC
     */
    public function getDiWrapperExampleServiceC(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['DiWrapper\Example\ServiceC'])) {
            return $this->services['DiWrapper\Example\ServiceC'];
        }

        $object = new \DiWrapper\Example\ServiceC();
        if (!$newInstance) {
            $this->services['DiWrapper\Example\ServiceC'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \DiWrapper\Example\ServiceD
     */
    public function getDiWrapperExampleServiceD(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['DiWrapper\Example\ServiceD'])) {
            return $this->services['DiWrapper\Example\ServiceD'];
        }

        $object = new \DiWrapper\Example\ServiceD($this->getDiWrapperExampleExampleDiFactory());
        if (!$newInstance) {
            $this->services['DiWrapper\Example\ServiceD'] = $object;
        }

        return $object;
    }
}
```

