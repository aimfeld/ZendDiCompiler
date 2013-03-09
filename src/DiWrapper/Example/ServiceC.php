<?php
/**
 * DiWrapper
 *
 * This source file is part of the DiWrapper package
 *
 * @package    DiWrapper
 * @subpackage Example
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace DiWrapper\Example;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class ServiceC
{
    /**
     * @param ExampleDiFactory $diFactory
     */
    public function __construct(ExampleDiFactory $diFactory)
    {
        $this->diFactory = $diFactory;
    }

    /**
     * Create runtim objects objects using a DiFactory which injects
     * RuntimeB's dependencies.
     */
    public function serviceMethod()
    {
        $classA = 'DiWrapper\Example\RuntimeA';
        $runtimeA1 = $this->diFactory->create($classA, array('hello', 'world'));
        $runtimeA2 = $this->diFactory->create($classA, array('goodbye', 'world'));

        $runtimeB1 = $this->diFactory->createRuntimeB('one', 1);
        $runtimeB2 = $this->diFactory->createRuntimeB('two', 2);
    }
}