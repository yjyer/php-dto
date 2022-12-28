<?php

declare(strict_types=1);

namespace yjyer\phpdto\reflect;

use Closure;
use ReflectionAttribute;

/**
 * 注解收集器
 *
 */
class AttributeCollector
{
    /**
     * 类注解反射库
     *
     * @var ReflectionAttribute[][] [$className => [$attributeName => ReflectionAttribute]]
     */
    protected static array $classAttributes = [];

    /**
     * 类方法注解反射库
     *
     * @var ReflectionAttribute[][][] [$className => [$methodName => [$attributeName => ReflectionAttribute]]]
     */
    protected static array $methodAttributes = [];

    /**
     * 类属性注解反射库
     *
     * @var ReflectionAttribute[][][] [$className => [$propertyName => [$attributeName => ReflectionAttribute]]]
     */
    protected static array $propertyAttributes = [];

    /**
     * 函数注解反射库
     *
     * @var ReflectionAttribute[][] [$functionName => [$attributeName => ReflectionAttribute]]
     */
    protected static array $functionAttributes = [];

    /**
     * 类注解反射
     *
     * @param string $class
     * @param string $attribute
     * @return ReflectionAttribute[]
     * @throws ReflectedException
     */
    public static function class(string $class, string $attribute): array
    {
        return self::$classAttributes[$class][$attribute] ??= ReflectionCollector::getClass($class)->getAttributes($attribute);
    }

    /**
     * 类方法注解反射
     *
     * @param string $class
     * @param string $method
     * @param string $attribute
     * @return ReflectionAttribute[]
     * @throws ReflectedException
     */
    public static function method(string $class, string $method, string $attribute): array
    {
        return self::$methodAttributes[$class][$method][$attribute] ??= ReflectionCollector::getMethod($class, $method)->getAttributes($attribute);
    }

    /**
     * 类属性注解反射
     *
     * @param string $class
     * @param string $property
     * @param string $attribute
     * @return ReflectionAttribute[]
     * @throws ReflectedException
     */
    public static function property(string $class, string $property, string $attribute): array
    {
        return self::$propertyAttributes[$class][$property][$attribute] ??= ReflectionCollector::getProperty($class, $property)->getAttributes($attribute);
    }

    /**
     * 类属性注解反射
     *
     * @param Closure|string $function
     * @param string $attribute
     * @return ReflectionAttribute[]
     * @throws ReflectedException
     */
    public static function function (Closure|string $function, string $attribute): array
    {
        return self::$functionAttributes[$function][$attribute] ??= ReflectionCollector::getFunction($function)->getAttributes($attribute);
    }
}
