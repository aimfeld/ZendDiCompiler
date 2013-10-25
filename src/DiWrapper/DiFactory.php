<?php
/**
 * ZendDiCompiler
 *
 * This source file is part of the ZendDiCompiler package
 *
 * @package    ZendDiCompiler
 * @license    New BSD License
 * @copyright  Copyright (c) 2013, aimfeld
 */

namespace ZendDiCompiler;


use ZendDiCompiler\Exception\RuntimeException;


/**
 * Extend and add custom factory methods
 *
 * This factory helps you create runtime objects with injected dependencies.
 * Extend to add your custom factory methods, if you don't want to pass all
 * runtime parameters in a singe array named $params.
 *
 * @package    ZendDiCompiler
 */
class DiFactory
{
    /**
     * @var ZendDiCompiler
     */
    protected $zendDiCompiler;

    /**
     * @param ZendDiCompiler $zendDiCompiler
     */
    public function __construct(ZendDiCompiler $zendDiCompiler)
    {
        $this->zendDiCompiler = $zendDiCompiler;
    }

    /**
     * @param string $className
     * @param array $params
     * @throws RuntimeException
     * @return object
     */
    public function create($className, array $params = array())
    {
        $instance = $this->zendDiCompiler->get($className, $params, true);

        if (is_null($instance)) {
            throw new RuntimeException(sprintf('Instance of class %s could not be created.', $className));
        }

        return $instance;
    }
}