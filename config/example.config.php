<?php

/**
 * Override the DI configuration in your project config.
 *
 * For info on the structure of the 'instance' and the 'preference' array,
 * see Zend\Di documentation.
 */
return array(
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
);
