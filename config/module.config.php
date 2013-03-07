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
            // e. g. 'vendor/provider/module/src',
        ),
        // Standard ZF2 key
        'definition' => array(
        ),
        'instance' => array(
            // Type preferences for abstract classes and interfaces.
            'preference' => array(
                // e. g. 'MyModule/SomeInterface' => 'MyModule/SomeClass',
            ),
            /* e. g.
            'MyModule/HelloWorldClass' => array('parameters' => array(
                'helloParam' => 'Hello',
                'worldParam' => 'World',
            )),*/
        ),
    ),
);
