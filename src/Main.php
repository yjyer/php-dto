<?php

declare(strict_types=1);

namespace yjyer\phpdto;

use yjyer\phpdto\analysis\DtoInfo;
use ReflectionMethod;

/**
 * dto入口类
 *
 */
class Main
{
    /**
     * 方法里注入调用
     *
     * @param mixed $reflect 控制方法的反射对象
     * @param array $args
     * @return array
     */
    public static function actionInject(ReflectionMethod $reflect, array $args, array $vars): array
    {
        //如果方法没有参数，则原样返回
        if (empty($args)) return $args;

        //1、获取传入反射方法里dto类信息 和 方法上的注解dto，用于验证
        $methodDto = DtoInfo::getMethodDto($reflect, $vars);
        //没有注解验证 获取 Dto类，则直接返回
        if (empty($methodDto)) return $args;

        $validateMessage = [];
        //2、如果有dto信息，则开始使用tp6自带的Validate模块验证
        $dtoValidateRet = $methodDto['dtoValidateRet'] ?? [];
        foreach ($dtoValidateRet as $key => $item) {
            if (empty($item['injectDtoValidate'])) continue;
            $validateRule = [];
            foreach ($item['injectDtoValidate'] as $vitem) {
                if (empty($vitem['v'])) continue;
                $validateRule["{$vitem['field']}|{$vitem['title']}"] = $vitem['v'];

                //注入自定义的验证提示文本
                foreach ($vitem['message'] as $msgItem) {
                    $validateMessage[$msgItem['key']] = $msgItem['value'];
                }
            }
            if ($validateRule) {
                $validate = \think\facade\Validate::rule($validateRule)
                    ->message($validateMessage);
                if (!$validate->check($vars)) {
                    throw new \think\exception\ValidateException($validate->getError(), 0);
                }
            }
        }

        //3、将注解类注入 args对应的位置
        $dtoInjectRet = $methodDto['dtoInjectRet'] ?? [];
        foreach ($dtoInjectRet as $key => $item) {
            $args[$item['position']] = $item['injectDtoClassObj'] ?? [];
        }

        return $args;
    }
}
