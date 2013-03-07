<?php
/** */

namespace DiWrapper\Example;

use Zend\Config\Config;

/** */
class C
{
    /**
     * @param Config $config
     * @param array $params
     */
    public function __construct(Config $config, array $params = array())
    {
        $this->param = $params;
    }
}