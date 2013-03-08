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


use DiWrapper\Exception\RuntimeException;


/**
 * Extend and add custom factory methods
 *
 * This factory helps you create runtime objects with injected dependencies.
 * Extend to add your custom factory methods, if you don't want to pass all
 * runtime parameters in a singe array named $params.
 *
 * @package    DiWrapper
 */
class DiFactory
{
    /**
     * @var DiWrapper
     */
    protected $diWrapper;

    /**
     * @param DiWrapper $diWrapper
     */
    public function __construct(DiWrapper $diWrapper)
    {
        $this->diWrapper = $diWrapper;
    }

    /**
     * @param string $className
     * @param array $params
     * @throws RuntimeException
     * @return object
     */
    public function create($className, array $params = array())
    {
        $instance = $this->diWrapper->get($className, $params, true);

        if (is_null($instance)) {
            throw new RuntimeException(sprintf('Instance of class %s could not be created.', $className));
        }

        return $instance;
    }
}