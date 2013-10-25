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

use ZendDiCompiler\DiFactory;

/**
 * @package    ZendDiCompiler
 * @subpackage Example
 */
class ServiceD
{
    /**
     * @param DiFactory $diFactory
     */
    public function __construct(DiFactory $diFactory)
    {
        $this->diFactory = $diFactory;
    }

    /**
     * Create runtim objects objects using a DiFactory which injects
     * RuntimeB's dependencies.
     */
    public function serviceMethod()
    {
        $runtimeA1 = $this->diFactory->create('ZendDiCompiler\Example\RuntimeA', array('hello', 'world'));
        $runtimeA2 = $this->diFactory->create('ZendDiCompiler\Example\RuntimeA', array('goodbye', 'world'));
    }
}