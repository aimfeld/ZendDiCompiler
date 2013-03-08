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
        // Path for writing the generated service locator class. Must be writable!
        'write_path' => './data',

        // Directories that will be code-scanned
        'scan_directories' => array(
            // 'vendor/provider/module/src',
            // __DIR__ . '/../src/DiWrapper/Example',
        ),

        // Definitions
        'definition' => array(
            // Add definitions which are not covered by the code scanning process. Usually, this can be omitted.
        ),

        // Instance configuration
        'instance' => array(
            // Type preferences for abstract classes and interfaces.
            'preference' => array(
                // e. g. 'MyModule/SomeInterface' => 'MyModule/SomeClass',
            ),
            // Add instance configuration if there are specific parameters to be used for instance creation.
            // A good approach is to pass a Zend\Config\Config object to your classes instead of single
            // parameters. This makes things more flexible and avoids the need of configuring instance parameters.
            // Of course, third party modules may not follow this approach, so intances need to be configured here, e.g.
            'DiWrapper\Example\B' => array(
                'parameters' => array(
                    'someParam' => 'Hello',
                ),
            ),
        ),
    ),
);
