<?php
/** */

namespace DiWrapper\Example;

use Zend\Mvc\Controller\AbstractActionController;
use DiWrapper\DiWrapper;
use Zend\Config\Config;


/** */
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
    }
}
