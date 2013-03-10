<?php

/**
 * Override the DI configuration in your project config.
 *
 * For an example config, see example.config.php. For info on the structure of
 * the 'instance' and the 'preference' array, see Zend\Di documentation.
 */
return array(
    // DiWrapper configuration
    'diWrapper' => array(
        // Path for writing the generated service locator class. Must be writable!
        'writePath' => './data',
        // Directories that will be code-scanned
        'scanDirectories' => array(
            // e.g. 'vendor/provider/module/src',
        ),
        // Names of class constructor parameters which will be passed the $params array
        // when calling DiWrapper::get($class, $params)
        // disable like this: 'params' => false
        'paramArrayNames' => array(
            'params' => true,
        ),
    ),
    // ZF2 DI definition and instance configuration used by DiWrapper
    'di' => array(
        // Definitions
        'definition' => array(
        ),
        // Instance configuration
        'instance' => array(
            // Type preferences for abstract classes and interfaces.
            'preference' => array(
                // e.g. 'DiWrapper\Example\ExampleInterface' => 'DiWrapper\Example\ExampleImplementor',
            ),
            /* e.g.
            'DiWrapper\Example\ServiceB' => array('parameters' => array(
                'diParam' => 'Hello',
            )),*/
         ),
    ),
);
