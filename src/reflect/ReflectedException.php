<?php

declare(strict_types=1);

namespace yjyer\phpdto\reflect;

use Exception;

/**
 * 反射异常类
 *
 */
class ReflectedException extends Exception
{
    /**
     * 错误码：类不存在
     */
    public const CLASS_NOT_EXIST = 1;

    /**
     * 错误码：类方法不存在
     */
    public const METHOD_NOT_EXIST = 2;

    /**
     * 错误码：类属性不存在
     */
    public const PROPERTY_NOT_EXIST = 3;

    /**
     * 错误码：函数不存在
     */
    public const FUNCTION_NOT_EXIST = 4;
}
