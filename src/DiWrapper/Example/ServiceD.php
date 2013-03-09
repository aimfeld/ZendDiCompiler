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

use DiWrapper\DiFactory;

/**
 * @package    DiWrapper
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
        $runtimeA1 = $this->diFactory->create('DiWrapper\Example\RuntimeA', array('hello', 'world'));
        $runtimeA2 = $this->diFactory->create('DiWrapper\Example\RuntimeA', array('goodbye', 'world'));
    }
}