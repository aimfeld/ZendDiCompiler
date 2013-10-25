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
class ServiceB
{
    /**
     * @param string $diParam
     */
    public function __construct($diParam)
    {
        $this->diParam = $diParam;
    }
}