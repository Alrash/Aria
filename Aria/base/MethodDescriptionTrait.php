<?php
/**
 * Author: Alrash
 * Date: 2017/02/04 22:07
 * Description: 复用判别方法可见行
 */

namespace Aria\base;

trait MethodDescriptionTrait {
    //检测方法是否是public标识的方法
    public function isPublic($method) {
        return (new \ReflectionMethod(get_called_class(), $method))->isPublic();
    }

    //检测方法是否是protected标识的方法
    public function isProtected($method) {
        return (new \ReflectionMethod(get_called_class(), $method))->isProtected();
    }

    //检测方法是否是private标识的方法
    public function isPrivate($method) {
        return (new \ReflectionMethod(get_called_class(), $method))->isPrivate();
    }

    //检测方法是否是static标识的方法
    public function isStatic($method) {
        return (new \ReflectionMethod(get_called_class(), $method))->isStatic();
    }
}