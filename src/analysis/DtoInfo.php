<?php

declare(strict_types=1);

namespace yjyer\phpdto\analysis;

use yjyer\phpdto\reflect\{AttributeCollector, ReflectionCollector};
use ReflectionMethod;


/**
 * dto信息获取
 *
 */
class DtoInfo
{
    /**
     * 获取方法里的dto类信息
     *
     * @param ReflectionMethod $reflect 方法反射
     * @param array $vars 界面传入参数
     * @return array
     */
    public static function getMethodDto(ReflectionMethod $reflect, array $vars): array
    {
        //是否需要解析dto
        $needAnalysisDto = false;
        //dto字段集合
        $dtoInjectRet = [];
        //需要验证的集合
        $dtoValidateRet = [];

        //1、获取反射方法里属性，paramDto，并获取dto信息，用于验证 
        $paramAttrs  = ReflectionCollector::getActionParamDtoAttr($reflect);

        //记录验证集合(paramDto优先提醒)
        $dtoValidateRet[] = [
            'type' => 'ParamDto',
            'dto' => '',
            'injectDtoValidate' =>  $paramAttrs  ?? []
        ];

        //2、获取传入反射方法里dto类信息，用于验证
        $params = $reflect->getParameters();
        foreach ($params as $key => $item) {
 
            if (!empty($item->getType()) && !$item->getType()->isBuiltin()) {
                //如果不是内置类型，则再次判断是否为Dto
                $attrIsDto = AttributeCollector::class(strval($item->getType()), 'yjyer\phpdto\attribute\IsDto');
                if (!empty($attrIsDto)) {
                    $needAnalysisDto = true;

                    $dtoClassReflect = ReflectionCollector::getClass(strval($item->getType()));

                    $props = ReflectionCollector::getPropertiesToArray($dtoClassReflect, $vars, $paramAttrs);

                    //解析dto类，返回字段数组
                    $dtoInjectRet[] = [
                        'position' => $item->getPosition(),
                        'injectDtoClassObj' => $props['injectDtoClassObj'] ?? []
                    ];

                    //记录验证集合
                    $dtoValidateRet[] = [
                        'type' => 'dtoClass',
                        'dto' => $item->getName(),
                        'injectDtoValidate' => $props['injectDtoValidate'] ?? []
                    ];
                }
            }
        }



        return [
            'dtoInjectRet' => $dtoInjectRet,
            'dtoValidateRet' => $dtoValidateRet,
        ];
    }
}
