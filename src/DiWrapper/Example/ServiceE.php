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
class ServiceE
{
    /**
     * @param ExampleDiFactory $diFactory
     */
    public function __construct(ExampleDiFactory $diFactory)
    {
        $this->diFactory = $diFactory;
    }

    /**
     * Create runtime objects using a DiFactory which injects RuntimeB's dependencies.
     */
    public function serviceMethod()
    {
        $runtimeB1 = $this->diFactory->createRuntimeB('one', 1);
        $runtimeB2 = $this->diFactory->createRuntimeB('two', 2);
    }
}