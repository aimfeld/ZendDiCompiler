<?php
/** */

namespace DiWrapper\Example;

/** */
class A
{
    /**
     * @param B $b
     */
    public function __construct(B $b)
    {
        $this->b = $b;
    }
}