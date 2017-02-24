<?php
/**
 * Author: Alrash
 * Date: 2017/01/16 23:01
 * Description: 数据类对象父类
 *
 * 思想来自Yii2.0
 *
 * 大方向：
 *  (1) 方便类内属性调用，不必使用set/get方法托长代码（基本同Java封装思想）
 *  (2) 禁止作为字符串调用（__toString）
 *  (3) 禁止调用不可见方法（__Call）
 *  (4) 禁止将类作为函数方法调用（__invoke）
 *
 * fix点：
 * 需要将SetMethodLimitTrait.php合并进来，同理扩展__get方法（$object->property = something的问题）
 * 需要了解__invoke方法实际情况
 * by 2017-02-22 18:50
 */

namespace Aria\base;

class Object {

    /*
     * 重载魔术方法__get
     * 四种处理：
     *  (1) get方法存在，则调用get方法，返回成员变量值
     *  (2) get方法不存在，但是set方法存在，抛出忽略调用异常
     *  (3) 两个方法都不存在，有该属性，抛出忽略调用异常
     *  (4) 无，抛出未知属性异常
     * */
    public function __get($name) {
        // TODO: Implement __get() method.

        $getter = "get" . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter();
        } elseif (method_exists($this, 'set' . $name)) {
            throw new InvalidCallException('Get only write property -- ' . get_called_class() . '::' . $name . '.');
        } elseif (property_exists($this, $name)) {
            throw new InvalidCallException('Could not do anything with property -- ' . get_called_class() . '::' . $name . '.');
        } else {
            throw new UnknownPropertyException('Could not found property -- ' . get_called_class() . '::' . $name . '.');
        }
    }

    /*
     * 重载魔术方法__set
     * 四种处理，几乎同__get
     * */
    public function __set($name, $value) {
        // TODO: Implement __set() method.

        $setter = "set" . $name;
        if (method_exists($this, $setter)) {
            $this->$setter($value);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Get only read property -- ' . get_called_class() . '::' . $name . '.');
        } elseif (property_exists($this, $name)) {
            throw new InvalidCallException('Could not do anything with property -- ' . get_called_class() . '::' . $name . '.');
        } else {
            throw new UnknownPropertyException('Could not found property -- ' . get_called_class() . '::' . $name . '.');
        }
    }

    /*
     * 检查不可见属性是否设置
     * */
    public function __isset($name) {
        // TODO: Implement __isset() method.

        $getter = 'get' . $name;
        if (method_exists($this, $getter)) {
            return $this->$getter() !== null;
        } else {
            return false;
        }
    }

    /*
     * 注销不可见属性
     *  若存在该属性，则调用set方法，将该属性的值设置为null
     * */
    public function __unset($name) {
        // TODO: Implement __unset() method.

        $setter = 'set' . $name;
        if (method_exists($this, $setter)) {
            $this->$setter(null);
        } elseif (method_exists($this, 'get' . $name)) {
            throw new InvalidCallException('Unsetting read-only property: ' . get_called_class() . '::' . $name);
        }
    }

    /*
     * 调用trigger_error，触发用户级错误，绕过__toString魔术方法不能抛出异常的限制
     * */
    public function __toString() {
        // TODO: Implement __toString() method.

        trigger_error('Could not transfer ' . get_called_class() . ' to string.', E_USER_ERROR);
        return '';
    }

    /*
     * 禁止调用不可见方法
     * */
    public function __call($name, $arguments) {
        // TODO: Implement __call() method.

        if (method_exists($this, $name)) {
            throw new InvalidCallException('Call invisible function -- ' . get_called_class() . '::' . $name . '.');
        } else {
            throw new UnknownMethodException('Call unknown function -- ' . get_called_class() . '::' . $name . '.');
        }
    }

    /*
     * */
    public static function __callStatic($name, $arguments) {
        // TODO: Implement __callStatic() method.

        if (method_exists(self::class, $name)) {
            throw new InvalidCallException('Call invisible static function -- ' . self::class . '::' . $name . '.');
        } else {
            throw new UnknownMethodException('Call unknown static function -- ' . self::class . '::' . $name . '.');
        }
    }

    /*
     * 重载魔术方法__invoke，直接抛出忽略调用异常
     * */
    public function __invoke() {
        // TODO: Implement __invoke() method.
        throw new InvalidCallException('Could not call ' . get_called_class() . ' class as a function.');
    }

    /**
     * 能够获得属性？
     * @param $name
     * @param $checkVar
     * @return true or false
     * */
    public function canGetProperty($name, $checkVar = true) {
        return method_exists($this, 'get' . $name) || $checkVar && property_exists($this, $name);
    }

    public function canSetProperty($name, $checkVar = true) {
        return method_exists($this, 'set' . $name) || $checkVar && property_exists($this, $name);
    }

    public function hasProperty($name, $checkVar = true) {
        return $this->canGetProperty($name, $checkVar) || $this->canSetProperty($name, $checkVar);
    }

    public function hasMethod($name) {
        return method_exists($this, $name);
    }
}