<?php
/**
 * Author: Alrash
 * Date: 2017/02/04 22:55
 * Description:
 */

namespace Aria\base;

trait SetMethodLimitTrait {
    use MethodDescriptionTrait;

    /**
     * 初始化时，会调用$this->setXXX()方法，若是protected方法，则不能正确调用（这里是使用trait服用初始化init方法）
     * 这里是为了防止这一现象而重写__set方法
     *
     * 但是trait（复制），作用域或解释的成什么？
     *
     *
     * 额。。。好像不对 by 2017-02-08
     *
     * @param $name string
     * @param $value string
     * @throws InvalidCallException
     * */
    public function __set($name, $value) {
        // TODO: Implement __set() method.

        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            if (!$this->isPublic($setter)) {
                throw new InvalidCallException(get_called_class() . '::' . $setter . ' method is not public method!');
            }
        }

        if (is_subclass_of($this, '\Aria\base\Object')) {
            Object::__set($name, $value);
        } else {
            parent::__set($name, $value);
        }
    }
}