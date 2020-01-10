<?php

/**
 * Override the DI configuration in your project config.
 *
 * For info on the structure of the 'instance' and the 'preference' array,
 * see Laminas\Di documentation.
 */
return [
    // ZendDiCompiler configuration
    'zendDiCompiler' => [
        // Directories that will be code-scanned
        'scanDirectories' => [
            // e.g. 'vendor/provider/module/src',
            __DIR__ . '/../src/ZendDiCompiler/Example',
        ],
    ],
    // ZF2 DI definition and instance configuration used by ZendDiCompiler
    'di'             => [
        // Instance configuration
        'instance' => [
            // Type preferences for abstract classes and interfaces.
            'preference'                      => [
                'ZendDiCompiler\Example\ExampleInterface' => 'ZendDiCompiler\Example\ExampleImplementor',
            ],
            // Add instance configuration if there are specific parameters to be used for instance creation.
            'ZendDiCompiler\Example\ServiceB' => [
                'parameters' => [
                'diParam' => 'Hello',
                ]
            ],
        ],
    ],
];
