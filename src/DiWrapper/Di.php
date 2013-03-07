<?php
/**
 * DiWrapper
 *
 * This source file is part of the DiWrapper package
 *
 * @package    DiWrapper
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace DiWrapper;

/**
 * @package    DiWrapper
 */
class Di extends \Zend\Di\Di
{
    public function __clone()
    {
        $this->definitions = clone $this->definitions;
        $this->instanceManager = clone $this->instanceManager;
    }
}