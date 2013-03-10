<?php

/**
 * Override the DI configuration in your project config.
 *
 * For info on the structure of the 'instance' and the 'preference' array,
 * see Zend\Di documentation.
 */
return array(
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
);
