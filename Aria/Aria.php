<?php
/**
 * Author: Alrash
 * Date: 2017/01/17 21:35
 * Description: 项目各类环境设置
 */

namespace Aria;

use Aria\base\BaseApp;
use Aria\base\UnknownClassException;

class Aria {
    /*
     * 类名映射数组
     * 存放文件定义的映射，比如文件名与类名不同或不是顶层文件又没有命名空间，导致不能正确加载文件
     * 需要从setClassMap导入配置，无需在这里编写
     * */
    private static $classMap = [];

    public static $app;

    public static function init() {
        self::$app = new BaseApp();
        self::$app->setEnv();
    }

    /*
     * 注册自动加载函数
     * */
    public static function register() {
        spl_autoload_register([self::class, 'autoload'], true, true);
        require_once(APP_ROOT . '/vendor/autoload.php');
    }

    /*
     * 自动加载类
     * 检测三步：
     *  (1) 是否在映射关系中存在
     *  (2) 是否是命名空间内的类
     *  (3) 第三方类(composer)，存在于register内
     * */
    protected static function autoload($className) {
        if (isset(static::$classMap[$className])) {
            $classPath = static::$classMap[$className];
        } elseif (strpos($className, '\\') !== false) {
            $className = trim($className, '\\');
            $classPath = APP_ROOT . '/' . str_replace('\\', '/', $className) . '.php';
        } else {
            return;
        }

        if (file_exists($classPath)) {
            require($classPath);
        } else {
            throw new UnknownClassException('Call unknown class ' . $className . '.');
        }
    }

    /**
     * 设置classMap变量
     * @param $path 需要包含的classMap.php上层路径路径
     *
     * 注：
     *  越后方的有限度越高
     * */
    public static function setClassMap($path) {
        if (!is_array($path)) {
            $path = [$path];
        }

        foreach ($path as $configPath) {
            $configFile = $configPath . '/classMap.php';
            if (file_exists($configFile)) {
                static::$classMap = array_merge(static::$classMap, require($configFile));
            }
        }
    }

    /*
     * 动态添加映射关系
     * 参数同上，注意点同上
     * */
    public static function addClassMap($config = []) {
        if (is_array($config)) {
            static::$classMap = array_merge(static::$classMap, $config);
        }
    }
}