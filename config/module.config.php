<?php

/**
 * Override the DI configuration in your project config.
 *
 * For an example config, see example.config.php. For info on the structure of
 * the 'instance' and the 'preference' array, see Zend\Di documentation.
 */
return [
    // ZendDiCompiler configuration
    'zendDiCompiler' => [
        // Set to false, if your application does not use Zend\Mvc and the onBootstrap event is therefore not called.
        'useZendMvc'                  => true,

        // Directories that will be code-scanned
        'scanDirectories'             => [
            // e.g. 'vendor/provider/module/src',
        ],

        // Names of class constructor parameters which will be passed the $params array
        // when calling ZendDiCompiler::get($class, $params)
        // disable like this: 'params' => false
        'paramArrayNames'             => [
            'zdcParams' => true,
        ],

        // Path for writing the generated service locator class. Must be writable!
        'writePath'                   => './data/ZendDiCompiler',

        // Code scan log file
        'scanLogFileName'             => 'code-scan.log',

        // Component dependency analysis file
        'componentDependencyFileName' => 'component-dependency-info.txt',

        // Class name of the generated service locator
        'serviceLocatorClass'         => 'GeneratedServiceLocator',

        // Class name of the generated temporary service locator in case of detected runtime error.
        'tempServiceLocatorClass'     => 'TempServiceLocator',

    ],
    // ZF2 DI definition and instance configuration used by ZendDiCompiler
    'di'             => [
        // Definitions
        'definition' => [],

        // Instance configuration
        'instance'   => [

            // Type preferences for abstract classes and interfaces.
            'preference' => [
                // e.g. 'ZendDiCompiler\Example\ExampleInterface' => 'ZendDiCompiler\Example\ExampleImplementor',
            ],
            /* e.g.
            'ZendDiCompiler\Example\ServiceB' => array('parameters' => array(
                'diParam' => 'Hello',
            )),*/
        ],
    ],
];
