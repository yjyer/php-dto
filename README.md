php-dto
===============

> 运行环境要求PHP8.0或以上

## 主要新特性

* 支持类似Java DTO
* 

## 安装


安装
~~~
composer require yjyer/php-dto
~~~

卸载
~~~
composer remove yjyer/php-dto
~~~



## 使用方法

在thinkphp6框架文件：/vendor/topthink/framework/src/think/Container.php 里的方法：invokeReflectMethod 下增加如下代码，如：
 
~~~php
/**
    * dto模块运行入口
    * 1、此代码需要手动增加
    * 2、每次更新tp核心框架后，如果此方法（Container/invokeReflectMethod）没有此代码，则需要增加
    */
$args = \yjyer\phpdto\Main::actionInject($reflect, $args, $vars);
~~~

完整方法如：
~~~php
/**
     * 调用反射执行类的方法 支持参数绑定
     * @access public
     * @param object $instance 对象实例
     * @param mixed  $reflect  反射类
     * @param array  $vars     参数
     * @return mixed
     */
public function invokeReflectMethod($instance, $reflect, array $vars = [])
{
    $args = $this->bindParams($reflect, $vars);

    /**
        * dto模块运行入口
        * 1、此代码需要手动增加
        * 2、每次更新tp核心框架后，如果此方法（Container/invokeReflectMethod）没有此代码，则需要增加
        */
    $args = \yjyer\phpdto\Main::actionInject($reflect, $args, $vars);

    return $reflect->invokeArgs($instance, $args);
}
~~~

## dto类配置
~~~php

<?php

declare(strict_types=1);

namespace app\account\dto;

use yjyer\phpdto\attribute\{IsDto, Validate, Field};

/**
 * 验证类
 */
#[IsDto(dto: 1)]
class UserDto
{
    #[Validate(v: 'require|max:3')]
    #[Field(title: "姓名", default: "张三")]
    public string $name;

    #[Validate(v: 'require|number|between:1,120', between: '有自定义则显示自定义提示：年龄不要输入太多或太小啊')]
    #[Field(title: "年龄", default: "")]
    public int $age;

    #[Validate(v: 'number|between:1,100')]
    #[Field(title: "位置", default: "1")]
    public int $position;

    #[Validate(v: 'email|require|max:3')]
    #[Field(title: "邮箱", default: "")]
    public string $email;
}


~~~

Validate的验证规则，和thinkphp6是一样的用法，查看：https://www.kancloud.cn/manual/thinkphp6_0/1037625

## 控制器内的使用
~~~php

<?php

declare(strict_types=1);

namespace app\account\controller;

use app\account\dto\UserDto;
use yjyer\phpdto\attribute\ParamDto;

class Index
{

    /**
     *
     * 用户信息方法
     *  
     */
    #[ParamDto(field: "age", title: "年龄", default: "18", v: 'number|between:1,120')]
    public function user(UserDto $userDto)
    {
        //1、输出已经注入UserDto内的参数
        //2、这里能拿到的数据，都是通过了验证的数据
        //3、如果ParamDto里有和Dto类(如UserDto)类里相同的字段，则优先使用ParamDto的配置
        dd($userDto);
        return '您好！这是一个[account]示例应用，请参考';
    }
}

~~~