<?php
/**
 * ZendDiCompiler
 *
 * This source file is part of the ZendDiCompiler package
 *
 * @package    ZendDiCompiler
 * @subpackage Example
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace ZendDiCompiler\Example;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class ServiceF
{
    /**
     * @param ExampleInterface $example
     */
    public function __construct(ExampleInterface $example)
    {
        // ExampleImplementor is injected since it is a type preference for ExampleInterface
        $this->example = $example;
    }

    /**
     * Create runtime objects using a DiFactory which injects RuntimeB's dependencies.
     */
    public function serviceMethod()
    {
        $this->example->example();
    }
}