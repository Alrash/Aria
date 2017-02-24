<?php
/**
 * Author: Alrash
 * Date: 2017/02/11 15:01
 * Description: 简单封装Session
 */

namespace Aria\base;

use Aria\Aria;

class Session extends Object implements SessionInterface {
    use SingletonTrait;

    private $session = [];
    /**
     * 默认配置文件
     * @var array
     */
    private $config = [
        'path' => '/tmp',
        'name' => 'PHPSESSID',
        'lefttime' => 1440,
        'times' => 10,
    ];

    /**
     * 调整session配置，开启session
     * @throws \Exception
     */
    public function open() {
        // TODO: Implement open() method.
        if (session_status() != PHP_SESSION_ACTIVE) {
            //存放路径不存在，则创建该目录
            if (file_exists($this->config['path']) === false) {
                if (mkdir($this->config['path'], 0700, true) === false) {
                    throw new \Exception('create dir "' . $this->config['path'] . '" failed!');
                }
            }

            //获取医用的sessionID
            @$sessionID = Aria::$app->cookie->getByName($this->config['name']);
            @$sessionID = isset($sessionID) ? $sessionID : $_COOKIE[$this->config['name']];
            if (isset($sessionID)){
                session_id($sessionID);
            }
            //session设置
            ini_set('session.gc_probability', '1');
            ini_set('session.gc_divisor', '100');
            ini_set('session.use_only_cookies', 'on');
            ini_set('session.gc_maxlifetime', $this->config['lefttime']);
            ini_set('session.name', $this->config['name']);
            ini_set('session.save_path', $this->config['path']);

            session_start();

            //取消全局变量_SESSION中的所有值
            foreach ($_SESSION as $key => $value) {
                $this->session[$key] = $value;
                unset($_SESSION[$key]);
            }
        }
    }

    /**
     * 简单的防止脚本之类的过度访问
     *
     * bug:
     * 依赖session，不能防止“游客”使用脚本解析
     * by 2017-02-21 22:14
     *
     * @return bool
     */
    public function prevent(): bool {
        if (empty($this->getByName('LAST_VISIT'))) {
            $this->set('LAST_VISIT', time());
        }
        if (empty($this->getByName('TIMES'))) {
            $this->set('TIMES', 0);
        }

        $now = time();
        if ($now - $this->getByName('LAST_VISIT') === 0) {
            if ($this->getByName('TIMES') > $this->config['times']) {
                return false;
            }else {
                return true;
            }
        }else {
            $this->set('LAST_VISIT', $now);
            $this->set('TIMES', 0);
            return true;
        }
    }

    public function set($name, $value) {
        // TODO: Implement set() method.
        $this->session[$name] = $value;
    }

    public function get(): array {
        // TODO: Implement get() method.
        return $this->session;
    }

    public function getByName($name) {
        // TODO: Implement getByName() method.
        if ($this->hasSession($name)) {
            return $this->session[$name];
        }else {
            return null;
        }
    }

    public function hasSession($name): bool {
        // TODO: Implement hasSession() method.
        return isset($this->session[$name]);
    }

    /**
     * @param array $config
     */
    protected function setConfig(array $config) {
        $this->config = array_merge($this->config, $config);
    }

    public function __destruct() {
        // TODO: Implement __destruct() method.
        foreach ($_SESSION as $key => $value) {
            unset($_SESSION[$key]);
        }
        foreach ($this->session as $key => $value) {
            $_SESSION[$key] = $value;
        }
    }
}