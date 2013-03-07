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

        // Of course we could also contructor-inject B, this is just for illustration
        $b = $diWrapper->get('DiWrapper\Example\B');

        // And here we generate a runtime object, automatically injecting the config
        $c = $diWrapper->get('DiWrapper\Example\C', array('hello' => 'world'));
    }
}
