<?php
/**
 * Author: Alrash
 * Date: 2017/01/27 20:46
 * Description:
 */

namespace Aria\base;

use Aria\Aria;

/**
 * Class CallbackTrait
 * @package Aria\base
 */
trait CallbackTrait {
    /**
     * 存储调用方法名
     *  因为callbackReflection之类函数均调用callbackReflectionArgsParams方法
     * @var null
     */
    private $__methodName__ = null;

    /**
     * 构造方法无参数，并且无参数调用$className::$methodName方法
     *  内部调用callbackReflectionArgsParams方法处理
     *
     * @param string $className 类名
     * @param string $methodName 调用方法名
     * @param bool $invalidAccessible 是否忽略调用方法可见性
     * @return mixed 返回调用返回值
     */
    public function callbackReflection($className, $methodName, $invalidAccessible = true) {
        $this->__methodName__ = __METHOD__;
        return $this->callbackReflectionArgsParams($className, $methodName, [], [], $invalidAccessible);
    }

    /**
     * 构造方法含参数，无参数调用$className::$methodName方法
     *  内部调用callbackReflectionArgsParams方法处理
     *
     * @param string $className 类名
     * @param string $methodName 调用方法名
     * @param array $args 构造方法参数列表
     * @param bool $invalidAccessible 是否忽略调用方法可见性
     * @return mixed 返回调用返回值
     */
    public function callbackReflectionArgs($className, $methodName, array $args = [], $invalidAccessible = true) {
        $this->__methodName__ = __METHOD__;
        return $this->callbackReflectionArgsParams($className, $methodName, $args, [], $invalidAccessible);
    }

    /**
     * 构造方法无参数，有参数调用$className::$methodName方法
     *  内部调用callbackReflectionArgsParams方法处理
     *
     * @param string $className 类名
     * @param string $methodName 调用方法名
     * @param array $params 调用方法参数列表
     * @param bool $invalidAccessible 是否忽略调用方法可见性
     * @return mixed 返回调用返回值
     */
    public function callbackReflectionParams($className, $methodName, array $params = [], $invalidAccessible = true) {
        $this->__methodName__ = __METHOD__;
        return $this->callbackReflectionArgsParams($className, $methodName, [], $params, $invalidAccessible);
    }

    /**
     * 使用反射回调方法
     *  注：不支持调用单例模式类型的类（因本项目中单例模式类均带参初始化，这里防止参数误用，导致之后调用获取错误实例）
     *
     * @param string $className 类名
     * @param string $methodName 方法名
     * @param array $args 默认[] 构造函数参数 [$config, $params]
     * @param array $params 默认[] 方法参数 [$argv[0], $argv[1], $argv[2], ...]
     * @param bool $invalidAccessible 默认true 是否忽略方法可见性
     * @return mixed 返回调用方法返回值
     * @throws InvalidCallException 不忽略可见性时，调用不可见方法时，抛出的异常
     * @throws ParamException 调用本方法时，参数类型不匹配时，抛出的异常
     * @throws LogicException 调用的类为单例模式系列类时，抛出的异常
     */
    public function callbackReflectionArgsParams($className, $methodName, array $args = [], array $params = [], $invalidAccessible = true) {
        if (!isset($this->__methodName__)) {
            $this->__methodName__ = __METHOD__;
        }

        /*
         * 参数检测
         * */
        if (!isset($className) || !is_string($className)) {
            throw new ParamException('Function ' . $this->__methodName__ . ' need string $className.');
        }

        if (!isset($methodName) || !is_string($methodName)) {
            throw new ParamException('Function ' . $this->__methodName__ . ' need string $methodName.');
        }

        if (!isset($args)) {
            $args = [];
        } elseif (!is_array($args)) {
            throw new ParamException('Function ' . $this->__methodName__ . ' need array $args.');
        }

        if (!isset($params)) {
            $params = [];
        } elseif (!is_array($params)) {
            throw  new ParamException('Function ' . $this->__methodName__ . ' need array $params.');
        }

        /*
         * 调用反射类运行
         * */
        $class = new \ReflectionClass($className);
        $trait = $class->getTraitNames();
        if (isset($trait)
            && in_array(Aria::$app->singletonTraitName, $trait) || in_array(trim(Aria::$app->singletonTraitName, '\\'), $trait)
        ) {
            throw new LogicException('The callback reflection function does not support singleton pattern.');
            /*
             * 单例模式$instance形式
             * 注意：因为上面new class时已经包含单例类，这时因为static关键字的关系，getInstance([], [])会直接进入内存全局（静态）区
             * */
            /*
            //$class = new \ReflectionClass($className::getInstance());
            $instance_config = isset($args['config']) ? $args['config'] : [];
            $instance_params = isset($args['params']) ? $args['params'] : [];
            $instance = $className::getInstance($instance_config, $instance_params);*/
        } else {
            $instance = $class->newInstanceArgs($args);
        }
        $method = $class->getMethod($methodName);

        if ($invalidAccessible || $method->isPublic()) {
            //有点想写$method->isPublic() || $method->setAccessible(true);
            if (!$method->isPublic())
                $method->setAccessible(true);

            return $method->invokeArgs($instance, $params);
        } else {
            throw new InvalidCallException('Call inaccessible function ' . $methodName . ' in class ' . $className . '.');
        }
    }

    /**
     * 构造方法无参数，并且无参数调用$className::$methodName方法
     *  内部调用callbackReflectionMixedArgsParams方法处理
     *
     * @param string $className 类名
     * @param string $methodName 调用方法名
     * @param bool $invalidAccessible 是否忽略调用方法可见性
     * @return mixed 返回调用返回值
     */
    public function callbackReflectionMixed($className, $methodName, $invalidAccessible = true) {
        $this->__methodName__ = __METHOD__;
        return $this->callbackReflectionMixedArgsParams($className, $methodName, [], [], $invalidAccessible);
    }

    /**
     * 构造方法含参数，无参数调用$className::$methodName方法
     *  内部调用callbackReflectionMixedArgsParams方法处理
     *
     * @param string $className 类名
     * @param string $methodName 调用方法名
     * @param array $args 构造方法参数列表
     * @param bool $invalidAccessible 是否忽略调用方法可见性
     * @return mixed 返回调用返回值
     */
    public function callbackReflectionMixedArgs($className, $methodName, array $args = [], $invalidAccessible = true) {
        $this->__methodName__ = __METHOD__;
        return $this->callbackReflectionMixedArgsParams($className, $methodName, $args, [], $invalidAccessible);
    }

    /**
     * 构造方法无参数，有参数调用$className::$methodName方法
     *  内部调用callbackReflectionMixedArgsParams方法处理
     *
     * @param string $className 类名
     * @param string $methodName 调用方法名
     * @param array $params 调用方法参数列表
     * @param bool $invalidAccessible 是否忽略调用方法可见性
     * @return mixed 返回调用返回值
     */
    public function callbackReflectionMixedParams($className, $methodName, array $params = [], $invalidAccessible = true) {
        $this->__methodName__ = __METHOD__;
        return $this->callbackReflectionMixedArgsParams($className, $methodName, [], $params, $invalidAccessible);
    }

    /**
     * 使用反射调用类内方法，支持单例模式（慎用）
     *  注：这里虽然支持单例模式对象，但是最终会释放生成的实例（调用前未实例化）
     *  实现的方法有点绕（所以说，为什么getConstructor()->setAccessible(true)没有用，哪里都没有查到）：
     *      (1) 检查\Aria\base\Singleton内的$instance，含不含有需要调用的实例，没有(2)，有(3)
     *      (2) 检查是否为\Aria\base\Singleton的子类(ReflectionClass()->isSubclassOf(...))，是设置不存在实例标志为true
     *      (3) ReflectionClass工作中
     *      (4) 根据不存在实例标志决定是否释放$instance[$className]
     *      ps: 不存在实例标识实际使用的是isExistedInstance（存在实例标识，默认为false）
     *
     * @param string $className 类名
     * @param string $methodName 方法名
     * @param array $args 默认[] 构造函数参数 [$config, $params]
     * @param array $params 默认[] 方法参数 [$argv[0], $argv[1], $argv[2], ...]
     * @param bool $invalidAccessible 默认true 是否忽略方法可见性
     * @return mixed 返回调用方法返回值
     * @throws InvalidCallException 不忽略可见性时，调用不可见方法时，抛出的异常
     * @throws ParamException 调用本方法时，参数类型不匹配时，抛出的异常
     */
    public function callbackReflectionMixedArgsParams($className, $methodName, array $args = [], array $params = [], $invalidAccessible = true) {
        if (!isset($this->__methodName__)) {
            $this->__methodName__ = __METHOD__;
        }

        /*
         * 参数检测
         * */
        if (!isset($className) || !is_string($className)) {
            throw new ParamException('Function ' . $this->__methodName__ . ' need string $className.');
        }

        if (!isset($methodName) || !is_string($methodName)) {
            throw new ParamException('Function ' . $this->__methodName__ . ' need string $methodName.');
        }

        if (!isset($args)) {
            $args = [];
        } elseif (!is_array($args)) {
            throw new ParamException('Function ' . $this->__methodName__ . ' need array $args.');
        }

        if (!isset($params)) {
            $params = [];
        } elseif (!is_array($params)) {
            throw  new ParamException('Function ' . $this->__methodName__ . ' need array $params.');
        }

        /* *
         * **本注释已无用，但是为删去**
         *
         * 这里反射SingletonList是为了检测是否已经存在$className所指代的实例
         * $singletonList为获取\Aria\base\SingletonList::$list属性存放的数组(通过getList方法)
         * */
        //$singletonList = $this->callbackReflection('\\Aria\\base\\SingletonList', 'getList');

        //获取已存在的单例类列表
        $singletonList = SingletonList::getList();

        /*
         * $isExistInstance 是否是已经存在的实例（为方法结束，取消实例化准备）
         * $isSingleton 是否是单例模式对象
         * */
        $isExistedInstance = false;
        $isSingleton = false;

        /*
         * 检测实例的存在性
         *  存在即为单例模式实例 -> $isExistedInstance = $isSingleton = true;
         * */
        if (array_key_exists(trim($className, '\\'), $singletonList) || array_key_exists($className, $singletonList)) {
            $isExistedInstance = true;
            $isSingleton = true;
        }

        /*
         * 通过上面的检测，表示不是已经存在的单例模式对象实例，但是其还是有可能是单例模式对象
         * 检测方式：是否类名存在\Aria\base\SingletonList::list中
         * */
        if (!$isSingleton) {
            $class = new \ReflectionClass($className);
            $trait = $class->getTraitNames();
            if (isset($trait)
                && in_array(Aria::$app->singletonTraitName, $trait) || in_array(trim(Aria::$app->singletonTraitName, '\\'), $trait)
            ) {
                $isSingleton = true;

                /*
                 * 因为getInstance(Array = [], Array = [])为静态方法，当new \ReflectionClass($className)时，
                 *      已经存放至内存全局区，并没有使用args初始化，当后期调用getInstance时，并没有实行想要的初始化
                 *      工作（总结：因为static的特性，使得初始化工作不是预想的）
                 * */
                $calledClassName = array_key_exists($className, $singletonList) ? $className : trim($className, '\\');
                SingletonList::removeList($calledClassName);
            } else {
                //实例化非单例模式对象
                $instance = $class->newInstanceArgs($args);
            }
        }

        /*
         * 分解初始化信息，获取$className实例，实例化ReflectionClass类
         * */
        if ($isSingleton) {
            $instance_config = isset($args['config']) ? $args['config'] : [];
            $instance_params = isset($args['params']) ? $args['params'] : [];
            $instance = $className::getInstance($instance_config, $instance_params);
            $class = new \ReflectionClass($className::getInstance());
        }

        $method = $class->getMethod($methodName);

        if ($invalidAccessible || $method->isPublic()) {
            //有点想写$method->isPublic() || $method->setAccessible(true);
            if (!$method->isPublic())
                $method->setAccessible(true);

            $result = $method->invokeArgs($instance, $params);
        } else {
            throw new InvalidCallException('Call inaccessible function ' . $methodName . ' in class ' . $className . '.');
        }

        /*
         * 删除本类创建的单例模式对象实例
         * */
        if ($isSingleton && !$isExistedInstance) {
            //$calledClassName = array_key_exists($className, $singletonList) ? $className : trim($className, '\\');
            SingletonList::removeList($calledClassName);
        }

        return $result;
    }
}