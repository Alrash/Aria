<?php
/**
 * Author: Alrash
 * Date: 2017/02/11 15:01
 * Description: cookie类
 *
 * 单例模式类
 */

namespace Aria\base;

class Cookie extends Object implements CookieInterface{
    use SingletonTrait, SetMethodLimitTrait;

    //已经计算好的存储时间
    const hour = 3600;
    const day = 86400;              //3600 * 24
    const week = 604800;            //3600 * 24 * 7
    const month = 259200;           //3600 * 24 * 30
    const year = 31536000;          //3600 * 24 * 365

    //存放cookie信息
    private $cookie = [];
    //cookie配置
    private $config;

    public function init(array $config = [], array $params = []) {
        $this->__init__($config, $params);

        //注销存在的cookie，编入$this->cookie
        foreach ($_COOKIE as $key => $value) {
            $this->cookie[$key] = $value;
            unset($_COOKIE[$key]);
        }
    }

    /**
     * 类内部更新cookie数组
     * @param $name
     * @param $value
     */
    protected function updateCookie($name, $value){
        $this->cookie[$name] = $value;
    }

    /**
     * 设置cookie组
     * 实际内部调用setcookie函数
     * @param $name
     * @param $value
     * @param int $time
     * @param string $path
     */
    public function set($name, $value, $time = self::week, $path = '/') {
        // TODO: Implement set() method.
        setcookie($name, $value, time() + $time, $path);
        $this->updateCookie($name, $value);
    }

    /**
     * 返回hash值
     * 内部调用sha256，使用一半的字符串(abcd 使用 bc)
     * @param $value
     * @param string $salt
     * @return string
     */
    public function hash(string $value, string $salt = ''): string {
        // TODO: Implement hash() method.
        $length = strlen($value, $salt);
        return hash('sha256', substr($value . $salt, floor($length / 4), floor($length / 2)));
    }

    public function get(): array {
        // TODO: Implement get() method.
        return $this->cookie;
    }

    public function getByName(string $name) {
        // TODO: Implement getByName() method.
        if ($this->hasCookie($name)){
            return $this->cookie[$name];
        }else {
            return null;
        }
    }

    public function hasCookie(string $name): bool {
        // TODO: Implement hasSession() method.
        return isset($this->cookie[$name]);
    }

    /**
     * @param mixed $config
     */
    protected function setConfig($config) {
        $this->config = $config;
    }

    /**
     * @return mixed
     */
    public function getConfig() {
        return $this->config;
    }
}