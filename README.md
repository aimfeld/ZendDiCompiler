# Table of Contents

* [Introduction](#introduction)
* [Features](#features)
* [Caveats](#caveats)
* [Installation](#installation)
* [Usage](#usage)
    * [Dependency injection container vs. service locator](#dependency-injection-container-vs-service-locator)
    * [Configuration](#configuration)
    * [Shared instances](#shared-instances)
    * [Type preferences](#type-preferences)
* [Examples](#examples)
    * [Using ZendDiCompiler to create a controller](#using-zenddicompiler-to-create-a-controller)
    * [Using the DiFactory to create runtime objects with dependencies](#using-the-difactory-to-create-runtime-objects-with-dependencies)
        * [Passing all runtime parameters in a single array](#passing-all-runtime-parameters-in-a-single-array)
        * [Passing custom runtime parameters](#passing-custom-runtime-parameters)
* [Generated code and info](#generated-code-and-info)
    * [Factory code](#factory-code)
    * [Code scan log](#code-scan-log)
    * [Component dependency info](#component-dependency-info)

# Introduction

Are you tired of writing tons of factory code (closures) for the Zend\ServiceManager in your Zend Framework 2 application?
Are outdated factory methods causing bugs? This can all be avoided by using ZendDiCompiler!

**ZendDiCompiler** is a Zend Framework 2 module that uses auto-generated factory code for dependency-injection.
It saves you a lot of work, since there's **no need anymore for writing
Zend\ServiceManager factory closures** and keeping them up-to-date manually.

ZendDiCompiler scans your code using **Zend\Di** and creates factory methods automatically. If the factory methods are outdated, ZendDiCompiler
updates them in the background. Therefore, you **develop faster**, **avoid bugs** due to outdated factory methods, and
experience **great performance** in production!

# Features

- **Code scanning** for creating DI definitions and **automatic factory code generation**.
- Can deal with **shared instances** and **type preferences**.
- Allows for **custom code introspection strategies** (by default, only constructors are scanned).
- Can be used as a **complement to Zend\ServiceManager**.
- Detection of outdated generated factory code and **automatic rescanning** (great for development).
- Can create new instances or reuse instances created before.
- Can be used as a **factory for runtime objects** combining DI and passing of runtime parameters.
- **Greater perfomance** and less memory consumption, as compared to using Zend\Di\Di with cached definitions.

# Caveats

- [Setter injection and interface injection](http://framework.zend.com/manual/current/en/tutorials/quickstart.di.html) are not supported yet. Instances must be injected via constructor injection (which I recommend over the two other methods anyway).
- Using ZendDiCompiler makes sense if you develop a large application or a framework. For smaller applications, ZendDiCompiler may be overkill and you should handle instantiation using Zend\ServiceManager callback methods.

# Installation

This module is [available](https://packagist.org/packages/aimfeld/ZendDiCompiler) on [Packagist](https://packagist.org).
In your project's `composer.json` use:

```
{
    "require": {
        "aimfeld/zend-di-compiler": "1.*"
    }
}
```

For PHP 7, install version 2.x, for PHP 5.4-5.6, use version 1.x.

Make sure you have a _writable_ `data` folder in your application root directory, see
[ZendSkeletonApplication](https://github.com/zendframework/ZendSkeletonApplication). Put a `.gitignore` file in it with
the following content:

```
*
!.gitignore
```

Add `'ZendDiCompiler'` to the modules array in your `application.config.php`. ZendDiCompiler must be the loaded _after_ the
modules where it is used:

```php
'modules' => array(
    'SomeModule',
    'Application',
    'ZendDiCompiler',
),
```

# Usage

## Dependency injection container vs. service locator

Is ZendDiCompiler a _dependency injection container (DiC)_ or a _service locator (SL)_? Well, that depends on where you use it.
ZendDiCompiler can be used as a _DiC_ to [create your controllers](#using-zenddicompiler-to-create-a-controller) in your module class
and inject the controller dependencies _from outside_. Pure _DiC_ usage implies that ZendDiCompiler is used only during the bootstrap
process and disposed _before_ the controller is dispatched. This has been coined the
"[Register Resolve Release](http://blog.ploeh.dk/2010/09/29/TheRegisterResolveReleasepattern/) pattern" and is
[the recommended way](http://stackoverflow.com/a/1994455/94289) by experts like Mark Seemann and others.

As soon as you inject the ZendDiCompiler itself into your controllers and other classes, you are using it as a _service locator_.
The ZF2 MVC architecture is based on controller classes with action methods. Given this architecture, controller dependencies become
numerous very quickly. In order to avoid bloated controller constructors, it makes sense to inject ZendDiCompiler as a
single dependency into ZF2 controller classes and use it to pull the other dependencies from inside the controllers.
This means using it as a _service locator_, just like `Zend\ServiceManager` is typically used.

ZendDiCompiler is also used as a _service locator_ inside of the provided `ZendDiCompiler\DiFactory` which is very useful for
[creating runtime objects with dependencies](#using-the-difactory-to-create-runtime-objects-with-dependencies). This
avoids a lot of [abstract factory code](http://stackoverflow.com/a/1945023/94289) you would otherwise have to write.
Besides ZF2 controllers, I recommend _not_ to inject ZendDiCompiler directly anywhere. If you need a service in one of your
classes, just ask for it in the constructor. If you need to create runtime objects with dependencies, inject
DiFactory or your extended version of it with [custom creation methods](#passing-custom-runtime-parameters).


## Configuration

ZendDiCompiler uses standard [Zend\Di configuration](http://framework.zend.com/manual/2.1/en/modules/zend.di.configuration.html)
(which is not well documented yet). To make things easier, see [example.config.php](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/example.config.php) for
examples of how to specify:

- Directories for the code scanner
- Instance configuration
- Type preferences

For a full list of configuration options, see [module.config.php](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/module.config.php)

ZendDiCompiler creates a `GeneratedServiceLocator` class in the `data` directory and automatically refreshes it when constructors change during
development. However, if you e.g. change parameters in the [instance configuration](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/example.config.php),
you have to manually delete `data/GeneratedServiceLocator.php` to force a refresh. In your staging and production
deployment/update process, make sure that `data/GeneratedServiceLocator.php` is deleted!

## Shared instances

You need to provide shared instances to [ZendDiCompiler::addSharedInstances()](https://github.com/aimfeld/ZendDiCompiler/blob/master/src/ZendDiCompiler/ZendDiCompiler.php) in
your application module's onBootstrap() method in the following cases (also see example below):

- The object to be injected is an instance of a class outside of the [scanned directories](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/example.config.php).
- The object to be injected requires some special bootstrapping (e.g. a session object).

Note that ZendDiCompiler provides some _default shared instances_ automatically
(see [ZendDiCompiler::getDefaultSharedInstances()](https://github.com/aimfeld/ZendDiCompiler/blob/master/src/ZendDiCompiler/ZendDiCompiler.php)).
The following _default shared instances_ can be constructor-injected without explicitly adding them:

- `ZendDiCompiler\ZendDiCompiler`
- `ZendDiCompiler\DiFactory`
- `Zend\Mvc\MvcEvent`
- `Zend\Config\Config`
- `Zend\View\Renderer\PhpRenderer`
- `Zend\Mvc\ApplicationInterface`
- `Zend\ServiceManager\ServiceLocatorInterface`
- `Zend\EventManager\EventManagerInterface`
- `Zend\Mvc\Router\RouteStackInterface`


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

We need to tell ZendDiCompiler which implementing class to inject for `ExampleInterface`. We specify `ExampleImplementor` as
a type preference for `ExampleInterface` in our [example config](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/example.config.php):

```php
'di' => array(
    'instance' => array(
        'preference' => array(
            'ZendDiCompiler\Example\ExampleInterface' => 'ZendDiCompiler\Example\ExampleImplementor',
        ),
    ),
),
```

ZendDiCompiler will now always inject `ExampleImplementor` for `ExampleInterface`. Calling
`ZendDiCompiler::get('ZendDiCompiler\Example\ExampleInterface')` will return the `ExampleImplementor`.

Type preferences can not only be used for interfaces and abstract classes, but for substituting classes
in general. They can even be used to deal with non-existing classes:

```php
'di' => array(
    'instance' => array(
        'preference' => array(
            'ZendDiCompiler\Example\NotExists' => 'ZendDiCompiler\Example\Exists',
        ),
    ),
),
```

Calling `ZendDiCompiler::get('ZendDiCompiler\Example\NotExists')` will return a `ZendDiCompiler\Example\Exists` instance.
Believe it or not, there are actually some good use cases for this.

# Examples

All examples sources listed here are included as [source code](https://github.com/aimfeld/ZendDiCompiler/tree/master/src/ZendDiCompiler/Example).

## Using ZendDiCompiler to create a controller

Let's say we want to use the ZendDiCompiler to create a controller class and inject some
dependencies. For illustriation, we also inject the ZendDiCompiler itself into the controller.
As mentioned [above](#dependency-injection-container-vs-service-locator), it
is a moot topic whether this is a good idea or not. But _if_ we decide to use the ZendDiCompiler _inside_ the controller to
get other dependencies, we can either inject it in the constructor or pull it from the ZF2 service locator
using `$this->serviceLocator->get('ZendDiCompiler')`.

In our example, we have the following classes:

ExampleController

```php
use Zend\Mvc\Controller\AbstractActionController;
use ZendDiCompiler\ZendDiCompiler;
use Zend\Config\Config;

class ExampleController extends AbstractActionController
{
    public function __construct(ZendDiCompiler $zendDiCompiler, ServiceA $serviceA, ServiceC $serviceC, Config $config)
    {
        $this->zendDiCompiler = $zendDiCompiler;
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

We add the example source directory as a scan directory for ZendDiCompiler. Since `ServiceB` has a parameter of unspecified type, we
have to specify a value to inject. A better approach for `ServiceB` would be to require the `Config` in its constructor
and retrieve the parameter from there, so we wouldn't need to specify an instance configuration. The
[configuration](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/example.config.php) for our example
looks like this:

```php
// ZendDiCompiler configuration
'zendDiCompiler' => array(
    // Directories that will be code-scanned
    'scanDirectories' => array(
        // e.g. 'vendor/provider/module/src',
        __DIR__ . '/../src/ZendDiCompiler/Example',
    ),
),
// ZF2 DI definition and instance configuration used by ZendDiCompiler
'di' => array(
    // Instance configuration
    'instance' => array(
        // Type preferences for abstract classes and interfaces.
        'preference' => array(
            'ZendDiCompiler\Example\ExampleInterface' => 'ZendDiCompiler\Example\ExampleImplementor',
        ),
        // Add instance configuration if there are specific parameters to be used for instance creation.
        'ZendDiCompiler\Example\ServiceB' => array('parameters' => array(
            'diParam' => 'Hello',
        )),
    ),
),
```

Now we can create the `ExampleController` in our application's [module class](https://github.com/aimfeld/ZendDiCompiler/blob/master/src/ZendDiCompiler/Example/Module.php).
For convenience, we retrieve the ZendDiCompiler from the service manager and assign it to a local variable (`$this->zendDiCompiler = $sm->get('ZendDiCompiler')`).
This makes it easier for writing `getControllerConfig()` or `getViewHelperConfig()` callbacks.

Since the `ServiceC` dependency requires some complicated initialization, we need to initialize it and add it as a shared instance to
ZendDiCompiler.

```php
class Module
{
    protected $zendDiCompiler;

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                // Suppose one of our routes specifies a controller named 'ExampleController'
                'ExampleController' => function() {
                    return $this->zendDiCompiler->get('ZendDiCompiler\Example\ExampleController');
                },
            ),
        );
    }

    public function onBootstrap(MvcEvent $mvcEvent)
    {
        $sm = $mvcEvent->getApplication()->getServiceManager();

        // Provide ZendDiCompiler as a local variable for convience
        $this->zendDiCompiler = $sm->get('ZendDiCompiler');

        // Set up shared instance
        $serviceC = new ServiceC;
        $serviceC->init($mvcEvent);

        // Provide shared instance
        $this->zendDiCompiler->addSharedInstances(array(
            'ZendDiCompiler\Example\ServiceC' => $serviceC,
        ));
    }
}
```

## Using the DiFactory to create runtime objects with dependencies

It is useful to distinguish two types of objects: _services_ and _runtime objects_. For _services_, all parameters should
be specified in the configuration (e.g. a config array wrapped in a `Zend\Config\Config` object). If class constructors
e.g. in third party code require some custom parameters, they can be specified in the
[instance configuration](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/example.config.php)).

_Runtime objects_, on the other hand, require at least one parameter which is determined at runtime only.
ZendDiCompiler provides `ZendDiCompiler\DiFactory` to help you create _runtime objects_ and inject their dependencies.

### Passing all runtime parameters in a single array

If you follow the convention of passing runtime parameters in a single array named `$zdcParams` as in `RuntimeA`,
things are very easy (the array name(s) can be configured in
[module.config.php](https://github.com/aimfeld/ZendDiCompiler/blob/master/config/module.config.php)):

```php
class RuntimeA
{
    public function __construct(Config $config, ServiceA $serviceA, array $zdcParams = array())
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->params = $zdcParams;
    }
}
```

ZendDiCompiler automatically injects `ZendDiCompiler\DiFactory` as a _default shared instance_. So
we can just use it to create `RuntimeA` objects in `ServiceD`. `RuntimeA`'s dependencies (the `Config` default shared instance
and `ServiceA`) are injected automatically, so you only need to provide the runtime parameters:

```php
use ZendDiCompiler\DiFactory;

class ServiceD
{
    public function __construct(DiFactory $diFactory)
    {
        $this->diFactory = $diFactory;
    }

    public function serviceMethod()
    {
        $runtimeA1 = $this->diFactory->create('ZendDiCompiler\Example\RuntimeA', array('hello', 'world'));
        $runtimeA2 = $this->diFactory->create('ZendDiCompiler\Example\RuntimeA', array('goodbye', 'world'));
    }
}
```

### Passing custom runtime parameters

If you can't or don't want to follow the convention of passing all runtime parameters in a single `$zdcParams` array,
ZendDiCompiler still is very useful. In that case, you can just extend a custom factory from `ZendDiCompiler\DiFactory` and
add your specific creation methods. `RuntimeB` requires two separate run time parameters:

```php
class RuntimeB
{
    public function __construct(Config $config, ServiceA $serviceA, $runtimeParam1, $runtimeParam2)
    {
        $this->config = $config;
        $this->serviceA = $serviceA;
        $this->runtimeParam1 = $runtimeParam1;
        $this->runtimeParam2 = $runtimeParam2;
    }
}
```

So we extend `ExampleDiFactory` from `ZendDiCompiler\DiFactory` and write a creation method `createRuntimeB`:

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
        $config = $this->zendDiCompiler->get('Zend\Config\Config');
        $serviceA = $this->zendDiCompiler->get('ZendDiCompiler\Example\ServiceA');
        return new RuntimeB($config, $serviceA, $runtimeParam1, $runtimeParam2);
    }
}
```

In `ServiceE`, we inject our extended factory. If the extended factory is located in a directory scanned by ZendDiCompiler,
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

# Generated code and info

## Factory code

ZendDiCompiler will automatically generate a service locator in the `data/ZendDiCompiler` directory and update it if constructors are changed
during development. Services can be created/retrieved using `ZendDiCompiler::get()`. If you need a new dependency in one of your
classes, you can just put it in the constructor and ZendDiCompiler will inject it for you.

Just for illustration, this is the generated service locator created by ZendDiCompiler and used in `ZendDiCompiler::get()`.

```php
namespace ZendDiCompiler;

use Zend\Di\ServiceLocator;

/**
 * Generated by ZendDiCompiler\Generator (2013-03-07 21:11:39)
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
            case 'ZendDiCompiler\Example\ExampleController':
                return $this->getZendDiCompilerExampleExampleController($params, $newInstance);

            case 'ZendDiCompiler\Example\ExampleDiFactory':
                return $this->getZendDiCompilerExampleExampleDiFactory($params, $newInstance);

            case 'ZendDiCompiler\Example\ExampleImplementor':
                return $this->getZendDiCompilerExampleExampleImplementor($params, $newInstance);

            case 'ZendDiCompiler\Example\Module':
                return $this->getZendDiCompilerExampleModule($params, $newInstance);

            case 'ZendDiCompiler\Example\RuntimeA':
                return $this->getZendDiCompilerExampleRuntimeA($params, $newInstance);

            case 'ZendDiCompiler\Example\ServiceA':
                return $this->getZendDiCompilerExampleServiceA($params, $newInstance);

            case 'ZendDiCompiler\Example\ServiceB':
                return $this->getZendDiCompilerExampleServiceB($params, $newInstance);

            case 'ZendDiCompiler\Example\ServiceC':
                return $this->getZendDiCompilerExampleServiceC($params, $newInstance);

            case 'ZendDiCompiler\Example\ServiceD':
                return $this->getZendDiCompilerExampleServiceD($params, $newInstance);

            case 'ZendDiCompiler\Example\ServiceE':
                return $this->getZendDiCompilerExampleServiceE($params, $newInstance);

            case 'ZendDiCompiler\Example\ServiceF':
                return $this->getZendDiCompilerExampleServiceF($params, $newInstance);

            default:
                return parent::get($name, $params);
        }
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ExampleController
     */
    public function getZendDiCompilerExampleExampleController(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ExampleController'])) {
            return $this->services['ZendDiCompiler\Example\ExampleController'];
        }

        $object = new \ZendDiCompiler\Example\ExampleController($this->get('ZendDiCompiler\ZendDiCompiler'), $this->getZendDiCompilerExampleServiceA(), $this->getZendDiCompilerExampleServiceC(), $this->get('Zend\Config\Config'));
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ExampleController'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ExampleDiFactory
     */
    public function getZendDiCompilerExampleExampleDiFactory(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ExampleDiFactory'])) {
            return $this->services['ZendDiCompiler\Example\ExampleDiFactory'];
        }

        $object = new \ZendDiCompiler\Example\ExampleDiFactory($this->get('ZendDiCompiler\ZendDiCompiler'));
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ExampleDiFactory'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ExampleImplementor
     */
    public function getZendDiCompilerExampleExampleImplementor(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ExampleImplementor'])) {
            return $this->services['ZendDiCompiler\Example\ExampleImplementor'];
        }

        $object = new \ZendDiCompiler\Example\ExampleImplementor();
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ExampleImplementor'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\Module
     */
    public function getZendDiCompilerExampleModule(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\Module'])) {
            return $this->services['ZendDiCompiler\Example\Module'];
        }

        $object = new \ZendDiCompiler\Example\Module();
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\Module'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\RuntimeA
     */
    public function getZendDiCompilerExampleRuntimeA(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\RuntimeA'])) {
            return $this->services['ZendDiCompiler\Example\RuntimeA'];
        }

        $object = new \ZendDiCompiler\Example\RuntimeA($this->get('Zend\Config\Config'), $this->getZendDiCompilerExampleServiceA(), $params);
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\RuntimeA'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ServiceA
     */
    public function getZendDiCompilerExampleServiceA(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ServiceA'])) {
            return $this->services['ZendDiCompiler\Example\ServiceA'];
        }

        $object = new \ZendDiCompiler\Example\ServiceA($this->getZendDiCompilerExampleServiceB());
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ServiceA'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ServiceB
     */
    public function getZendDiCompilerExampleServiceB(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ServiceB'])) {
            return $this->services['ZendDiCompiler\Example\ServiceB'];
        }

        $object = new \ZendDiCompiler\Example\ServiceB('Hello');
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ServiceB'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ServiceC
     */
    public function getZendDiCompilerExampleServiceC(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ServiceC'])) {
            return $this->services['ZendDiCompiler\Example\ServiceC'];
        }

        $object = new \ZendDiCompiler\Example\ServiceC();
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ServiceC'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ServiceD
     */
    public function getZendDiCompilerExampleServiceD(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ServiceD'])) {
            return $this->services['ZendDiCompiler\Example\ServiceD'];
        }

        $object = new \ZendDiCompiler\Example\ServiceD($this->get('ZendDiCompiler\DiFactory'));
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ServiceD'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ServiceE
     */
    public function getZendDiCompilerExampleServiceE(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ServiceE'])) {
            return $this->services['ZendDiCompiler\Example\ServiceE'];
        }

        $object = new \ZendDiCompiler\Example\ServiceE($this->getZendDiCompilerExampleExampleDiFactory());
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ServiceE'] = $object;
        }

        return $object;
    }

    /**
     * @param array $params
     * @param bool $newInstance
     * @return \ZendDiCompiler\Example\ServiceF
     */
    public function getZendDiCompilerExampleServiceF(array $params = array(), $newInstance = false)
    {
        if (!$newInstance && isset($this->services['ZendDiCompiler\Example\ServiceF'])) {
            return $this->services['ZendDiCompiler\Example\ServiceF'];
        }

        $object = new \ZendDiCompiler\Example\ServiceF($this->getZendDiCompilerExampleExampleImplementor());
        if (!$newInstance) {
            $this->services['ZendDiCompiler\Example\ServiceF'] = $object;
        }

        return $object;
    }
}
```

## Code scan log

ZendDiCompiler logs problems found during code scanning in `data/ZendDiCompiler/code-scan.log`. If you can't retrieve an object from ZendDiCompiler, you will probably find the reason in this log. The most common problem is that you have untyped scalar parameters instead of a [parameter array](#passing-all-runtime-parameters-in-a-single-array) in your constructors without providing values in your [Zend\Di configuration](http://framework.zend.com/manual/current/en/modules/zend.di.configuration.html). Here's an example of the code scan log showing some problems:
```
INFO (6): Start generating service locator by code scanning.
DEBUG (7): Survey\Cache\Zf1CacheAdapter: Class Zend\Cache\Storage\StorageInterface could not be located in provided definitions.
DEBUG (7): Survey\DataAggregator\Aggregate: Missing instance/object for parameter data for Survey\DataAggregator\Aggregate::__construct
DEBUG (7): Survey\Db\Table\Rowset: Missing instance/object for parameter config for Survey\Db\Table\Rowset::__construct
DEBUG (7): Survey\DbValidate\ValidationResult: Missing instance/object for parameter errorCount for Survey\DbValidate\ValidationResult::__construct
DEBUG (7): Survey\Form\MessageContainer: Missing instance/object for parameter intro for Survey\Form\MessageContainer::__construct
DEBUG (7): Survey\Input\ValidationResult: Missing instance/object for parameter level for Survey\Input\ValidationResult::__construct
DEBUG (7): Survey\Mail\Mailer\MailerResult: Missing instance/object for parameter success for Survey\Mail\Mailer\MailerResult::__construct
DEBUG (7): Survey\Paginator\Adapter\DbSelect: Class Zend_Db_Select could not be located in provided definitions.
DEBUG (7): Survey\Pdf\Prince: Missing instance/object for parameter exePath for Survey\Pdf\Prince::__construct
DEBUG (7): Survey\SkipLogic\ConditionResult: Missing instance/object for parameter isTrue for Survey\SkipLogic\ConditionResult::__construct
DEBUG (7): Survey\System\SystemInfo: Missing instance/object for parameter lastEventFlowUpdate for Survey\System\SystemInfo::__construct
DEBUG (7): Survey\TokenLinker\ActionResult: Missing instance/object for parameter action for Survey\TokenLinker\ActionResult::__construct
DEBUG (7): Survey\UserSurveyManager\UpdateStatusesResult: Missing instance/object for parameter updatesCount for Survey\UserSurveyManager\UpdateStatusesResult::__construct
INFO (6): Code scanning finished.
INFO (6): Writing generated service locator to ./data/ZendDiCompiler/GeneratedServiceLocator.php.
INFO (6): Finished writing generated service locator to ./data/ZendDiCompiler/GeneratedServiceLocator.php.
```
In case of simple [value objects](http://martinfowler.com/bliki/ValueObject.html) without any service dependencies, I do not use dependency injection but create then with `new`, e.g. `ConditionResult::__construct($isTrue, $isCacheable, $allowFlip = true)`. These objects are not meant to be created with the [DiFactory](#using-the-difactory-to-create-runtime-objects-with-dependencies) and therefore, the `DEBUG` notice can be ignored.

## Component dependency info

As a bonus, ZendDiCompiler will write a `component-dependency-info.txt` file containing information about
which of the scanned components depend on which classes.

Scanned classes are grouped into components (e.g. the Zend\Mvc\MvcEvent class belongs to the Zend\Mvc component).
For every component, all constructor-injected classes are listed. This helps you analyze which components
depend on which classes of other components. Consider organizing your components into layers.
Each layer should depend on classes of the same or lower layers only.
Note that only constructor-injection is considered for this analysis, so the picture might be incomplete.

Here's an example of what you might see:
```
...
MyLibrary\Mail classes inject:
- MyLibrary\Mail\Transport
- MyLibrary\TextEngine\TextEngine
- Zend\Config\Config

MyLibrary\Validator classes inject:
- MyLibrary\Db\Tables
- MyLibrary\I18n\Translator\Translator
...
```

