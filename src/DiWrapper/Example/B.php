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
class B
{
    /**
     * @param string $someParam
     */
    public function __construct($someParam)
    {
        $this->someParam = $someParam;
    }
}