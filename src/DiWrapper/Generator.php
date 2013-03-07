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

use Zend\Code\Generator\DocBlockGenerator;
use Zend\Di\ServiceLocator\GeneratorInstance;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use DateTime;

/**
 * @package    DiWrapper
 */
class Generator extends \Zend\Di\ServiceLocator\Generator
{
    /**
     * Support for passing a $params array for instance creation
     */
    const PARAMS_ARRAY = '__paramsArray__';

    /**
     * Construct, configure, and return a PHP class file code generation object
     *
     * Creates a Zend\Code\Generator\FileGenerator object that has
     * created the specified class and service locator methods.
     *
     * @param  null|string                         $filename
     * @throws Exception\RuntimeException
     * @return FileGenerator
     */
    public function getCodeGenerator($filename = null)
    {
        $injector       = $this->injector;
        $im             = $injector->instanceManager();
        $indent         = '    ';
        $aliases        = $this->reduceAliases($im->getAliases());
        $caseStatements = array();
        $getters        = array();
        $definitions    = $injector->definitions();

        $fetched = array_unique(array_merge($definitions->getClasses(), $im->getAliases()));

        foreach ($fetched as $name) {
            // Filter out abstract classes and interfaces
            $class = new \ReflectionClass($name);
            if ($class->isAbstract() || $class->isInterface()) {
                continue;
            }

            $getter = $this->normalizeAlias($name);
            try {
                // Support for passing a $params array for instance creation
                $meta = $injector->newInstance($name, array('params' => self::PARAMS_ARRAY));
                $params = $meta->getParams();
            } catch (\Exception $e) {
                continue;
            }

            // Build parameter list for instantiation
            foreach ($params as $key => $param) {
                if ($param === self::PARAMS_ARRAY) {
                    // Support for passing a $params array for instance creation
                    $params[$key] = "\$params";
                } elseif (null === $param || is_scalar($param) || is_array($param)) {
                    $string = var_export($param, 1);
                    if (strstr($string, '::__set_state(')) {
                        throw new Exception\RuntimeException('Arguments in definitions may not contain objects');
                    }
                    $params[$key] = $string;
                } elseif ($param instanceof GeneratorInstance) {
                    /* @var $param GeneratorInstance */
                    $params[$key] = sprintf("\$this->%s()", $this->normalizeAlias($param->getName()));
                } elseif (is_object($param)) {
                    $objectClass = get_class($param);
                    if ($im->hasSharedInstance($objectClass)) {
                        $params[$key] = sprintf("\$this->get('%s')", $objectClass);
                    } else {
                        $message = sprintf('Unable to use object arguments when building containers. Encountered with "%s", parameter of type "%s"', $name, get_class($param));
                        throw new Exception\RuntimeException($message);
                    }
                }
            }

            // Strip null arguments from the end of the params list
            $reverseParams = array_reverse($params, true);
            foreach ($reverseParams as $key => $param) {
                if ('NULL' === $param) {
                    unset($params[$key]);
                    continue;
                }
                break;
            }

            // Create instantiation code
            $constructor = $meta->getConstructor();
            if ('__construct' != $constructor) {
                // Constructor callback
                $callback = var_export($constructor, 1);
                if (strstr($callback, '::__set_state(')) {
                    throw new Exception\RuntimeException('Unable to build containers that use callbacks requiring object instances');
                }
                if (count($params)) {
                    $creation = sprintf('$object = call_user_func(%s, %s);', $callback, implode(', ', $params));
                } else {
                    $creation = sprintf('$object = call_user_func(%s);', $callback);
                }
            } else {
                // Normal instantiation
                $className = '\\' . ltrim($name, '\\');
                $creation = sprintf('$object = new %s(%s);', $className, implode(', ', $params));
            }

            // Create method call code
            $methods = '';
            foreach ($meta->getMethods() as $methodData) {
                if (!isset($methodData['name']) && !isset($methodData['method'])) {
                    continue;
                }
                $methodName   = isset($methodData['name']) ? $methodData['name'] : $methodData['method'];
                $methodParams = $methodData['params'];

                // Create method parameter representation
                foreach ($methodParams as $key => $param) {
                    if (null === $param || is_scalar($param) || is_array($param)) {
                        $string = var_export($param, 1);
                        if (strstr($string, '::__set_state(')) {
                            throw new Exception\RuntimeException('Arguments in definitions may not contain objects');
                        }
                        $methodParams[$key] = $string;
                    } elseif ($param instanceof GeneratorInstance) {
                        $methodParams[$key] = sprintf("\$this->%s(\$params)", $this->normalizeAlias($param->getName()));
                    } else {
                        $message = sprintf('Unable to use object arguments when generating method calls. Encountered with class "%s", method "%s", parameter of type "%s"', $name, $methodName, get_class($param));
                        throw new Exception\RuntimeException($message);
                    }
                }

                // Strip null arguments from the end of the params list
                $reverseParams = array_reverse($methodParams, true);
                foreach ($reverseParams as $key => $param) {
                    if ('NULL' === $param) {
                        unset($methodParams[$key]);
                        continue;
                    }
                    break;
                }

                $methods .= sprintf("\$object->%s(%s);\n", $methodName, implode(', ', $methodParams));
            }

            // Generate caching statement
            $storage = '';
            $storage .= "if (!\$newInstance) {\n";
            $storage .= sprintf("%s\$this->services['%s'] = \$object;\n}\n\n", $indent, $name);


            // Start creating getter
            $getterBody = '';

            // Create fetch of stored service
            $getterBody .= sprintf("if (!\$newInstance && isset(\$this->services['%s'])) {\n", $name);
            $getterBody .= sprintf("%sreturn \$this->services['%s'];\n}\n\n", $indent, $name);


            // Creation and method calls
            $getterBody .= sprintf("%s\n", $creation);
            $getterBody .= $methods;

            // Stored service
            $getterBody .= $storage;

            // End getter body
            $getterBody .= "return \$object;\n";

            $getterDef = new MethodGenerator();
            $getterDef->setName($getter);
            $paramParam = new ParameterGenerator('params', 'array', array());
            $newInstanceParam = new ParameterGenerator('newInstance', 'bool', false);
            $getterDef->setParameters(array($paramParam, $newInstanceParam));
            $getterDef->setBody($getterBody);
            $getterDef->setDocBlock("@param array \$params\n@param bool \$newInstance\n@return \\$name");
            $getters[] = $getterDef;

            // Get cases for case statements
            $cases = array($name);
            if (isset($aliases[$name])) {
                $cases = array_merge($aliases[$name], $cases);
            }

            // Build case statement and store
            $statement = '';
            foreach ($cases as $value) {
                $statement .= sprintf("%scase '%s':\n", $indent, $value);
            }
            $statement .= sprintf("%sreturn \$this->%s(\$params, \$newInstance);\n", str_repeat($indent, 2), $getter);

            $caseStatements[] = $statement;
        }

        // Build get() method body
        $body = "if (!\$newInstance && isset(\$this->services[\$name])) {\n";
        $body .= sprintf("%sreturn \$this->services[\$name];\n}\n\n", $indent);

        // Build switch statement
        $body .= sprintf("switch (%s) {\n%s\n", '$name', implode("\n", $caseStatements));
        $body .= sprintf("%sdefault:\n%sreturn parent::get(%s, %s);\n", $indent, str_repeat($indent, 2), '$name', '$params');
        $body .= "}\n\n";

        // Build get() method
        $nameParam   = new ParameterGenerator('name');
        $paramsParam = new ParameterGenerator('params', 'array', array());
        $newInstanceParam = new ParameterGenerator('newInstance', 'bool', false);

        $get = new MethodGenerator();
        $get->setName('get');
        $get->setParameters(array($nameParam, $paramsParam, $newInstanceParam));
        $get->setDocBlock("@param string \$name\n@param array \$params\n@param bool \$newInstance\n@return mixed");
        $get->setBody($body);

        // Create getters for aliases
        $aliasMethods = array();
        foreach ($aliases as $class => $classAliases) {
            foreach ($classAliases as $alias) {
                $aliasMethods[] = $this->getCodeGenMethodFromAlias($alias, $class);
            }
        }

        // Create class code generation object
        $container = new ClassGenerator();
        $classDocBlockGenerator = new DocBlockGenerator();
        $now = (new DateTime('now'))->format('Y-m-d H:i:s');
        $classDocBlockGenerator->setShortDescription(
            sprintf("Generated by %s (%s)", get_class($this), $now));
        $container->setName($this->containerClass)
            ->setExtendedClass('ServiceLocator')
            ->addMethodFromGenerator($get)
            ->addMethods($getters)
            ->addMethods($aliasMethods)
            ->setDocBlock($classDocBlockGenerator);

        // Create PHP file code generation object
        $classFile = new FileGenerator();
        $classFile->setUse('Zend\Di\ServiceLocator')
            ->setClass($container);

        if (null !== $this->namespace) {
            $classFile->setNamespace($this->namespace);
        }

        if (null !== $filename) {
            $classFile->setFilename($filename);
        }

        return $classFile;
    }
}