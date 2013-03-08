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

use Zend\Mvc\Controller\AbstractActionController;
use DiWrapper\DiWrapper;
use Zend\Config\Config;

/**
 * @package    DiWrapper
 * @subpackage Example
 */
class ExampleController extends AbstractActionController
{
    /**
     * @param DiWrapper $diWrapper
     * @param Config $config
     * @param A $a
     */
    public function __construct(DiWrapper $diWrapper, Config $config, A $a)
    {
        $this->diWrapper = $diWrapper;
        $this->config = $config;
        $this->a = $a;

        // Of course we could also contructor-inject B, this is just for illustration
        $this->b = $diWrapper->get('DiWrapper\Example\B');

        // And here we use the DiWrapper as a runtime-object factory, automatically injecting the config
        $this->c = $diWrapper->get('DiWrapper\Example\C', array('hello' => 'world'), true);
    }
}
