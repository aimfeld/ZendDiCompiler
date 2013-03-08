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
class ExampleDiFactory extends DiFactory
{
    /**
     * Custom factory method with runtime params not passed as a $params array.
     *
     * @param $param1
     * @param $param2
     * @return D
     */
    public function createD($param1, $param2)
    {
        $config = $this->diWrapper->get('Zend\Config\Config'); /** @var \Zend\Config\Config $config */
        $a = $this->diWrapper->get('DiWrapper\Example\A'); /** @var \DiWrapper\Example\A $a */
        return new D($config, $a, $param1, $param2);
    }
}