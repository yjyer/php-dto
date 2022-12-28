<?php

declare(strict_types=1);

namespace yjyer\phpdto\reflect;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use ReflectionProperty;

/**
 * 反射收集器
 *
 */
class ReflectionCollector
{
    /**
     * 类型反射库
     *
     * @var ReflectionClass[] [$className => ReflectionClass]
     */
    protected static array $classReflections = [];

    /**
     * 类方法反射库
     *
     * @var ReflectionMethod[][] [$className => [$methodName => ReflectionMethod]]
     */
    protected static array $methodReflections = [];

    /**
     * 类属性反射库
     *
     * @var ReflectionProperty[][] [$className => [$propertyName => ReflectionProperty]]
     */
    protected static array $propertyReflections = [];

    /**
     * 函数反射库
     *
     * @var ReflectionFunction[][] [$functionName => [$propertyName => ReflectionFunction]]
     */
    protected static array $functionReflections = [];

    /**
     * 类反射
     *
     * @param string $class
     * @return ReflectionClass
     * @throws ReflectedException
     */
    public static function getClass(string $class): ReflectionClass
    {
        try {
            return self::$classReflections[$class] ??= new ReflectionClass($class);
        } catch (ReflectionException) {
            throw new ReflectedException($class, ReflectedException::CLASS_NOT_EXIST);
        }
    }

    /**
     * 类方法反射
     *
     * @param string $class
     * @param string $method
     * @return ReflectionMethod
     * @throws ReflectedException
     */
    public static function getMethod(string $class, string $method): ReflectionMethod
    {
        try {
            return self::$methodReflections[$class][$method] ??= self::getClass($class)->getMethod($method);
        } catch (ReflectionException) {
            throw new ReflectedException($class . '::' . $method, ReflectedException::METHOD_NOT_EXIST);
        }
    }

    /**
     * 类属性反射
     *
     * @param string $class
     * @param string $property
     * @return ReflectionProperty
     * @throws ReflectedException
     */
    public static function getProperty(string $class, string $property): ReflectionProperty
    {
        try {
            return self::$propertyReflections[$class][$property] ??= self::getClass($class)->getProperty($property);
        } catch (ReflectionException) {
            throw new ReflectedException($class . '::' . $property, ReflectedException::PROPERTY_NOT_EXIST);
        }
    }

    /**
     * 类方法反射
     *
     * @param Closure|string $function
     * @return ReflectionFunction
     * @throws ReflectedException
     */
    public static function getFunction(Closure|string $function): ReflectionFunction
    {
        try {
            return is_string($function)
                ? self::$functionReflections[$function] ??= new ReflectionFunction($function)
                : new ReflectionFunction($function);
        } catch (ReflectionException) {
            throw new ReflectedException($function, ReflectedException::FUNCTION_NOT_EXIST);
        }
    }


    /**
     * 根据类反射，得到属性数组
     *
     * @param ReflectionClass $reflect 反射类
     * @param Array $vars 界面传入参数
     * @param Array $paramAttrs 方法上的paramDto属性值
     * @return Array
     * @throws ReflectedException
     */
    public static function getPropertiesToArray(ReflectionClass $reflect, array $vars, array $paramAttrs = []): array
    {
        try {

            //实例化Dto类
            $dtoClassName = strval($reflect->getName());
            $dtoClass = new $dtoClassName();

            //得到dto类的所有字段属性信息
            $propertyLis = $reflect->getProperties();

            $dtoValidate = [];
            foreach ($propertyLis as $key => $item) {

                $propName = $item->getName(); //字段名，如 age

                if (!empty($paramAttrs)) {
                    $useParamDto = false;
                    //遍历paramDto，如果和dto类里有重复，则使用paramDto验证，当前类里的字段则不记录
                    foreach ($paramAttrs as $paramItem) {
                        if ($propName == $paramItem['field']) {
                            $useParamDto = true;
                            break;
                        }
                    }

                    if ($useParamDto) continue;
                }

                //得到属性标题信息，如 年龄
                $filedAttr = self::getPropertyFieldAttr($item);
                //获取验证器
                $validateAttr = self::getPropertyValidateAttr($item, $filedAttr, $propName);

                //合并数组
                foreach ($validateAttr as $vali) {
                    $vali['message'] = self::getValidateMessage($vali, $propName, $vali['title']);
                    array_push($dtoValidate, $vali);
                }

                //得到验证数据 
                $dtoClass->$propName = self::getPropertyValueByInput($item, $vars, $filedAttr);
            }

            return   [
                'injectDtoClassObj' =>  $dtoClass,
                'injectDtoValidate' =>  $dtoValidate,
            ];
        } catch (ReflectionException) {
            return [];
        }
    }

    /**
     * 根据validate属性，获取验证提示文本
     *
     * @param array $validateAttrItem
     * @param [type] $fieldName
     * @param [type] $fieldTitle
     * @return void
     */
    private static function getValidateMessage(array $validateAttrItem, $fieldName, $fieldTitle)
    {
        $message = [];
        //得到验证提示文本
        $vRules = explode('|', $validateAttrItem['v']);
        foreach ($vRules as $vrKey => $vrItem) {
            $ruleName = explode(':', $vrItem)[0] ?? 0;
            if (empty($validateAttrItem[$ruleName])) continue;
            //设置验证提示文本
            $message[] = [
                'key' => "{$fieldName}.{$ruleName}",
                'value' => str_replace('$', $fieldTitle ?? $fieldName, $validateAttrItem[$ruleName] ?? ''),
            ];
        }
        return $message;
    }

    /**
     * 从页面输入数据中比较数据赋值到 dto
     *
     * @param ReflectionProperty $propItem
     * @param array $vars
     * @return void
     */
    private static function getPropertyValueByInput(ReflectionProperty $propItem, array $vars, array $fieldAttr)
    {
        if (!$propItem) return '';

        //得到字段属性

        if (!empty($propItem->getType()) && $propItem->getType()->isBuiltin()) {
            //如果是内置的变量类型

            $val = $fieldAttr['default'] ?? '';
            if (!empty($vars[$propItem->getName()])) {
                //如果前台有传值，则再根据字段类型强转
                $val  = $vars[$propItem->getName()];
            }
            // else {
            //     //todo 如果前台没有传值，则使用字段属性里的默认值
            // }

            //设置字段类型
            settype($val, $propItem->getType()->getName());

            return $val;
        } else {
            //todo 非内置变量，特殊处理
            
        }
    }

    /**
     * 得到dto字段的属性注解信息
     *
     * @param ReflectionProperty $propItem
     * @return array
     */
    private static function getPropertyFieldAttr(ReflectionProperty $propItem): array
    {
        if (!$propItem) return '';

        //得到字段属性
        $fieldAttr = $propItem->getAttributes('yjyer\phpdto\attribute\Field')[0];

        return $fieldAttr->getArguments() ? $fieldAttr->getArguments() : [
            'name' => '',
            'default' => '',
        ];
    }

    /**
     * 得到dto字段的验证注解信息
     *
     * @param ReflectionProperty $propItem
     * @return array
     */
    private static function getPropertyValidateAttr(ReflectionProperty $propItem, array $fieldAttr, string $fieldName): array
    {
        if (!$propItem) return '';

        //得到字段属性
        $validateAttrs = $propItem->getAttributes('yjyer\phpdto\attribute\Validate');

        $retValidate = [];
        foreach ($validateAttrs as $key => $item) {

            $retValidate[] = array_merge($item->getArguments(), [
                'field' => $fieldName ?? '',
                'title' => $fieldAttr['title'] ?? '',
                'default' => $fieldAttr['default'] ?? ''
            ]);
        }

        return $retValidate;
    }


    /**
     * 得到方法里的 ParamDto注解信息，用于验证参数
     *
     * @param ReflectionMethod $propItem
     * @return array
     */
    public static function getActionParamDtoAttr(ReflectionMethod $methodRef): array
    {
        if (!$methodRef) return '';

        $validateAttrs = $methodRef->getAttributes('yjyer\phpdto\attribute\ParamDto');

        $retValidate = [];
        foreach ($validateAttrs as $key => $item) {
            $newItem = $item->getArguments();
            $newItem['message'] = self::getValidateMessage($newItem, $newItem['field'] ?? '', $newItem['title'] ?? '');
            $retValidate[] = $newItem;
        }

        return $retValidate;
    }
}
