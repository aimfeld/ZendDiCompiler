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

use Zend\Debug\Debug;
use Zend\Di\DefinitionList;

/**
 * @package    DiWrapper
 */
class ComponentDependencyDumper
{
    /**
     * Dumps component dependencies (injected classes)
     *
     * The dumped data shows component (not class) dependencies. Used for debugging only.
     *
     * @param DefinitionList $definitions
     */
    protected function dump(DefinitionList $definitions)
    {
        $classes = $definitions->getClasses();

        // Group classes into components
        $components = array();
        foreach ($classes as $className) {
            $offset = strpos($className, '\\') + 1;
            $offsetComponent = strpos($className, '\\', $offset);
            $component = substr($className, 0, $offsetComponent);
            $constructorParams = $definitions->getMethodParameters($className, '__construct');
            foreach ($constructorParams as $constructorParam) {
                $paramClassName = $constructorParam[1];
                if (isset($paramClassName) && // array params excluded
                    (!array_key_exists($component, $components) ||
                        !in_array($paramClassName, $components[$component]))
                ) {
                    $components[$component][] = $paramClassName;
                }
            }
        }

        ksort($components);

        Debug::dump($components);
    }
}