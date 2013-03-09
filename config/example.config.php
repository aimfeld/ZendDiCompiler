<?php

/**
 * Override the DI configuration in your project config.
 *
 * For info on the structure of the 'instance' and the 'preference' array,
 * see Zend\Di documentation.
 */
return array(
    // Definition and instance configuration
    'di' => array(
        // Directories that will be code-scanned
        'scan_directories' => array(
            // 'vendor/provider/module/src',
             __DIR__ . '/../src/DiWrapper/Example',
        ),
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
