<?php
/**
 * Author: Alrash
 * Date: 2017/01/22 21:33
 * Description: 单例
 */

namespace Aria\base;

trait SingletonTrait {
    private static $instance = null;

    //复用初始化方法
    use InitTrait {
        //防止使用单例模式重载init方法
        init as __init__;
    }

    protected function __construct(array $config = [], array $params = []) {
        $this->init($config, $params);
    }

    public function __get($name) {
        if ($name === 'instance') {
            throw new InvalidCallException('Could not get instance directly!');
        }

        return Object::__get($name); // TODO: Change the autogenerated stub
    }

    /**
     * 延时绑定，获取对象实例
     * @param array $config 配置
     * @param array $params 其余初始化参数参数
     * @return 对象实例
     */
    public static function getInstance(array $config = [], array $params = []) {
        if (!isset(self::$instance)) {
            self::$instance = new static($config, $params);
            SingletonList::addList(get_called_class());
        }
        return self::$instance;
    }

    //防止对象被克隆
    private function __clone() {
    }
}

/*
class Singleton extends Object{
    private static $instance = null;

    //复用初始化方法
    use InitTrait;

    protected function __construct(array $config = [], array $params = []){
        $this->init($config, $params);
    }

    public function __get($name){
        if ($name === 'instance'){
            throw new InvalidCallException('Could not get instance directly!');
        }

        return parent::__get($name); // TODO: Change the autogenerated stub
    }

    /**
     * 延时绑定，获取对象实例
     * @param array $config 配置
     * @param array $params 其余初始化参数参数
     * @return 对象实例
     */
/*public static function getInstance(array $config = [], array $params = []){
    $className = get_called_class();
    if (!isset(self::$instance[$className])){
        self::$instance[$className] = new static($config, $params);
    }
    return self::$instance[$className];
}

//防止对象被克隆
private function __clone(){
}
}*/