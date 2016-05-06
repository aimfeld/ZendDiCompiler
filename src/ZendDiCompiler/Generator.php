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

use Zend\Code\Generator\DocBlockGenerator;
use Zend\Code\Generator\ValueGenerator;
use Zend\Di\Di;
use Zend\Di\InstanceManager;
use Zend\Di\ServiceLocator;
use Zend\Di\ServiceLocator\GeneratorInstance;
use Zend\Code\Generator\MethodGenerator;
use Zend\Code\Generator\ParameterGenerator;
use Zend\Code\Generator\ClassGenerator;
use Zend\Code\Generator\FileGenerator;
use Zend\Code\Generator\PropertyGenerator;
use Zend\Config\Config;
use Zend\Log\Logger;
use ZendDiCompiler\Exception\RuntimeException;
use DateTime;
use ReflectionClass;

/**
 * @package    ZendDiCompiler
 */
class Generator extends \Zend\Di\ServiceLocator\Generator
{
    /**
     * Support for passing a $params array for instance creation
     */
    const PARAMS_ARRAY = '__paramsArray__';
    const INDENT = '    ';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var ValueGenerator
     */
    protected $emptyArrayGenerator;

    /**
     * @param Di     $injector
     * @param Config $config
     * @param Logger $logger
     */
    public function __construct(Di $injector, Config $config, Logger $logger)
    {
        $this->config              = $config;
        $this->logger              = $logger;
        $this->emptyArrayGenerator = new ValueGenerator([], ValueGenerator::TYPE_ARRAY_SHORT);

        parent::__construct($injector);
    }

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
        $im = $this->injector->instanceManager();
        $definitions = $this->injector->definitions();
        $classesOrAliases = array_unique(array_merge($definitions->getClasses(), $im->getAliases()));

        $generatorInstances = $this->getGeneratorInstances($classesOrAliases);

        $getterMethods = $this->getGetterMethods($generatorInstances);
        $aliasMethods = $this->getAliasMethods($im);
        $caseStatements = $this->createCaseStatements($generatorInstances);
        $get = $this->getGetMethod($caseStatements);

        // Create class code generation object
        $container = new ClassGenerator();
        $classDocBlockGenerator = new DocBlockGenerator();
        $now = (new DateTime('now'))->format('Y-m-d H:i:s');
        $classDocBlockGenerator->setShortDescription(
            sprintf("Generated by %s (%s)", get_class($this), $now));
        $container->setName($this->containerClass)
            ->setExtendedClass('ServiceLocator')
            ->addProperty('services', [], PropertyGenerator::FLAG_PUBLIC)
            ->addMethodFromGenerator($get)
            ->addMethods($getterMethods)
            ->addMethods($aliasMethods)
            ->setDocBlock($classDocBlockGenerator);

        // Create PHP file code generation object
        $classFile = new FileGenerator();
        $classFile->setUse(ServiceLocator::class)->setClass($container);

        if (null !== $this->namespace) {
            $classFile->setNamespace($this->namespace);
        }

        if (null !== $filename) {
            $classFile->setFilename($filename);
        }

        return $classFile;
    }

    /**
     * Creates the main get() method
     *
     * @param $caseStatements
     * @return MethodGenerator
     */
    public function getGetMethod($caseStatements)
    {
        // Build get() method body
        $body = "if (!\$newInstance && isset(\$this->services[\$name])) {\n";
        $body .= sprintf("%sreturn \$this->services[\$name];\n}\n\n", self::INDENT);

        // Build switch statement
        $body .= sprintf("switch (%s) {\n%s\n", '$name', implode("\n", $caseStatements));
        $body .= sprintf("%sdefault:\n%sreturn parent::get(%s, %s);\n", self::INDENT, str_repeat(self::INDENT, 2), '$name', '$params');
        $body .= "}\n\n";

        // Build get() method
        $nameParam        = new ParameterGenerator('name');
        $paramsParam      = new ParameterGenerator('params', 'array', $this->emptyArrayGenerator);
        $newInstanceParam = new ParameterGenerator('newInstance', 'bool', false);

        $get = new MethodGenerator();
        $get->setName('get');
        $get->setParameters([$nameParam, $paramsParam, $newInstanceParam]);
        $get->setDocBlock("@param string \$name\n@param array \$params\n@param bool \$newInstance\n@return mixed");
        $get->setBody($body);
        return $get;
    }

    /**
     * @param string[] $classesOrAliases
     * @return GeneratorInstance[]
     */
    protected function getGeneratorInstances(array &$classesOrAliases)
    {
        $paramArrayNames   = $this->config->get('zendDiCompiler')->paramArrayNames;
        $newInstanceParams = [];
        foreach ($paramArrayNames as $paramArrayName => $enabled) {
            // Allow for disabling param array names
            if ($enabled) {
                $newInstanceParams[$paramArrayName] = self::PARAMS_ARRAY;
            }
        }

        $instanceManager    = $this->injector->instanceManager();
        $generatorInstances = [];
        foreach ($classesOrAliases as $classOrAlias) {
            try {
                $generatorInstance = null;

                $class = new ReflectionClass($classOrAlias);

                if ($instanceManager->hasSharedInstance($classOrAlias)) {
                    $generatorInstance = $this->injector->get($classOrAlias);
                } elseif (!$class->isAbstract() && !$class->isInterface()) {
                    // Support for passing a $params array for instance creation
                    $generatorInstance = $this->injector->newInstance($classOrAlias, $newInstanceParams);
                }

                if ($generatorInstance instanceof GeneratorInstance) {
                    $generatorInstances[$classOrAlias] = $generatorInstance;
                }

            } catch (\Exception $e) {
                $this->logger->debug($classOrAlias . ': '. $e->getMessage());
                continue;
            }
        }

        return $generatorInstances;
    }

    /**
     * @param GeneratorInstance[] $generatorInstances
     * @throws Exception\RuntimeException
     * @return MethodGenerator[]
     */
    protected function getGetterMethods(array $generatorInstances)
    {
        $im      = $this->injector->instanceManager();
        $getters = [];

        foreach ($generatorInstances as $classOrAlias => $generatorInstance) {
            // Parameter list for instantiation
            $params = $this->getParams($classOrAlias, $generatorInstance, $im);

            // Create instantiation code
            $creationCode = $this->getCreationCode($classOrAlias, $generatorInstance, $params);

            // Create method call code
            $methodCallCode = $this->getMethodCallCode($classOrAlias, $generatorInstance);

            // Generate caching statement
            $storage = '';
            $storage .= "if (!\$newInstance) {\n";
            $storage .= sprintf("%s\$this->services[\\%s::class] = \$object;\n}\n\n", self::INDENT, $classOrAlias);

            // Start creating getter
            $getterBody = '';

            // Create fetch of stored service
            $getterBody .= sprintf("if (!\$newInstance && isset(\$this->services[\\%s::class])) {\n", $classOrAlias);
            $getterBody .= sprintf("%sreturn \$this->services[\\%s::class];\n}\n\n", self::INDENT, $classOrAlias);


            // Creation and method calls
            $getterBody .= sprintf("%s\n", $creationCode);
            $getterBody .= $methodCallCode;

            // Stored service
            $getterBody .= $storage;

            // End getter body
            $getterBody .= "return \$object;\n";

            $getterDef = new MethodGenerator();
            $getterDef->setName($this->normalizeAlias($classOrAlias));

            $paramParam       = new ParameterGenerator('params', 'array', $this->emptyArrayGenerator);
            $newInstanceParam = new ParameterGenerator('newInstance', 'bool', false);
            $getterDef->setParameters([$paramParam, $newInstanceParam]);
            $getterDef->setBody($getterBody);
            $getterDef->setDocBlock("@param array \$params\n@param bool \$newInstance\n@return \\$classOrAlias");
            $getters[] = $getterDef;
        }

        return $getters;
    }

    /**
     * @param InstanceManager $im
     * @return MethodGenerator[]
     */
    protected function getAliasMethods(InstanceManager $im)
    {
        $aliasMethods = [];
        $aliases      = $this->reduceAliases($im->getAliases());
        foreach ($aliases as $class => $classAliases) {
            foreach ($classAliases as $alias) {
                $aliasMethods[] = $this->getCodeGenMethodFromAlias($alias, $class);
            }
        }
        return $aliasMethods;
    }

    /**
     * @param GeneratorInstance[] $generatorInstances
     * @return string[]
     */
    protected function createCaseStatements(array $generatorInstances)
    {
        $caseStatements = [];

        foreach (array_keys($generatorInstances) as $classOrAlias) {
            // Get cases for case statements
            $cases = [$classOrAlias];
            if (isset($aliases[$classOrAlias])) {
                $cases = array_merge($aliases[$classOrAlias], $cases);
            }

            // Build case statement and store
            $getter = $this->normalizeAlias($classOrAlias);
            $statement = '';
            foreach ($cases as $value) {
                $statement .= sprintf("%scase \\%s::class:\n", self::INDENT, $value);
            }
            $statement .= sprintf("%sreturn \$this->%s(\$params, \$newInstance);\n", str_repeat(self::INDENT, 2), $getter);

            $caseStatements[] = $statement;
        }

        return $caseStatements;
    }

    /**
     * E.g. setter injection
     *
     * @param string $classOrAlias
     * @param GeneratorInstance $generatorInstance
     * @return string
     * @throws Exception\RuntimeException
     */
    protected function getMethodCallCode($classOrAlias, GeneratorInstance $generatorInstance)
    {
        $methods = '';
        foreach ($generatorInstance->getMethods() as $methodData) {
            if (!isset($methodData['name']) && !isset($methodData['method'])) {
                continue;
            }
            $methodName   = $methodData['name'] ?? $methodData['method'];
            $methodParams = $methodData['params'];

            // Create method parameter representation
            foreach ($methodParams as $key => $param) {
                if (null === $param || is_scalar($param) || is_array($param)) {
                    $string = var_export($param, 1);
                    if (false !== strpos($string, '::__set_state(')) {
                        $message = sprintf('%s: Arguments in definitions may not contain objects', $classOrAlias);
                        $this->logger->err($message);
                        throw new Exception\RuntimeException($message);
                    }
                    $methodParams[$key] = $string;
                } elseif ($param instanceof GeneratorInstance) {
                    $methodParams[$key] = sprintf("\$this->%s(\$params)", $this->normalizeAlias($param->getName()));
                } else {
                    $message = sprintf('%s: Unable to use object arguments when generating method calls (method "%s", parameter of type "%s")', $classOrAlias, $methodName, get_class($param));
                    $this->logger->err($message);
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
        return $methods;
    }

    /**
     * Create instantiation code
     *
     * @param $classOrAlias
     * @param GeneratorInstance $generatorInstance
     * @param string[] $params
     * @return string
     * @throws RuntimeException
     */
    protected function getCreationCode($classOrAlias, GeneratorInstance $generatorInstance, array &$params)
    {
        $constructor = $generatorInstance->getConstructor();
        if ('__construct' != $constructor) {
            // Constructor callback
            $callback = var_export($constructor, 1);
            if (false !== strpos($callback, '::__set_state(')) {
                throw new RuntimeException('Unable to build containers that use callbacks requiring object instances');
            }
            if (count($params)) {
                $creation = sprintf('$object = call_user_func(%s, %s);', $callback, implode(', ', $params));
            } else {
                $creation = sprintf('$object = call_user_func(%s);', $callback);
            }
        } else {
            // Normal instantiation
            $className = '\\' . ltrim($classOrAlias, '\\');
            $creation = sprintf('$object = new %s(%s);', $className, implode(', ', $params));
        }

        return $creation;
    }

    /**
     * Build parameter list for instantiation
     *
     * @param string $classOrAlias
     * @param GeneratorInstance $generatorInstance
     * @param InstanceManager $im
     * @throws Exception\RuntimeException
     * @return string
     */
    protected function getParams($classOrAlias, GeneratorInstance $generatorInstance, InstanceManager $im)
    {
        $params = $generatorInstance->getParams();

        foreach ($params as $key => $param) {
            if ($param === self::PARAMS_ARRAY) {
                // Support for passing a $params array for instance creation
                $params[$key] = "\$params";
            } elseif (null === $param || is_scalar($param) || is_array($param)) {
                $string = var_export($param, 1);
                if (false !== strpos($string, '::__set_state(')) {
                    $message = sprintf('%s: Arguments in definitions may not contain objects (parameter type "%s")', $classOrAlias, $string);
                    $this->logger->err($message);
                    throw new Exception\RuntimeException($message);
                }
                $params[$key] = $string;
            } elseif ($param instanceof GeneratorInstance) {
                $params[$key] = sprintf("\$this->%s()", $this->normalizeAlias($param->getName()));
            } elseif (is_object($param)) {
                $objectClassName = get_class($param);
                if (!$params[$key] = $this->getGetterMethodFromClass($im, $objectClassName)) {
                    $message = sprintf('%s: No shared instance for parameter "%s" class or its supertypes (abstract classes and interfaces).', $classOrAlias, $objectClassName);
                    $this->logger->err($message);
                    throw new RuntimeException($message);
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

        return $params;
    }

    /**
     * @param InstanceManager $im
     * @param string          $className
     *
     * @return null|string
     */
    protected function getGetterMethodFromClass(InstanceManager $im, $className)
    {
        if ($im->hasSharedInstance($className)) {
            return sprintf("\$this->get(\\%s::class)", $className);
        } else {
            $reflection = new ReflectionClass($className);

            // See if there are shared instances for interfaces implemented by the class and its parents
            foreach ($reflection->getInterfaceNames() as $interfaceName) {
                if ($im->hasSharedInstance($interfaceName)) {
                    return sprintf("\$this->get(\\%s::class)", $interfaceName);
                }
            }

            // See if there are shared instances for parent classes
            while ($parent = $reflection->getParentClass()) {
                $parentClass = $parent->getName();
                if ($im->hasSharedInstance($parentClass)) {
                    return sprintf("\$this->get(\\%s::class)", $parentClass);
                }
                $reflection = $parent;
            }
        }

        return null;
    }
}