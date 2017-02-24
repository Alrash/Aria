<?php
/**
 * Author: Alrash
 * Date: 2017/01/31 20:51
 * Description: 应用文件
 * (1) 设置应用时区
 * (2) 获取应用request, session, cookie
 * (3) 获取应用各种配置
 * (4) 处理url并执行操作
 */

namespace Aria\base;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class BaseApp extends Component implements DoActionInterface{
    use DoActionTrait;

    private $HTMLEncode = 'UTF-8';
    private $timezone = 'Asia/Shanghai';
    private $singletonTraitName = '\\Aria\\base\\SingletonTrait';

    private $request;
    private $session;
    private $cookie;

    private $message = [];
    private $config = [];       //setting
    private $routeConfig = [];
    private $viewMap = [];

    /**
     * 初始化
     */
    public function init() {
        parent::init();

        /*
         * 移除url自带的变量
         * */
        $this->removeMagicQuotes();
        $this->unregisterGlobals();

        //载入配置文件
        $this->config = $this->loadConfig();
        $this->message = $this->loadConfig('message');
        //覆盖性载入配置
        $this->routeConfig = $this->loadConfig('route', true);
        $this->viewMap = $this->loadConfig('viewMap', true);

        //创建单例模式对象
        $this->request = Request::getInstance();
        $this->cookie = Cookie::getInstance($this->config['cookie']);
        $this->session = Session::getInstance($this->config['session']);
    }

    /**
     * 项目运行
     */
    public function run() {
        try{
            //开启session
            $this->session->open();

            //创建路由类
            $Route = new Route($this->routeConfig);

            //原始路由
            $this->request->originRoute = $Route->getOriginRoute();

            /*
             * 防止某个用户过多的访问
             * 但是本预防方法，依赖sessionID，无法预防不依赖session的游客
             */
            if ($this->session->prevent()) {
                //获取真实路由，并拆分
                $route = $this->splitRoute($Route->getRealRoute());
            }else {
                $route = $this->splitRoute($Route->getErrorRoute());
            }

            //开始动作
            $this->doAction($route['class'], $route['action'], $route['params']);
        } catch (\ReflectionException $e){
            //反射类/动作不存在的时候，做路由配置文件中的error动作
            try {
                $route = $this->splitRoute($Route->getErrorRoute());
                $this->doAction($route['class'], $route['action'], $route['params']);
            } catch (\Exception $e) {
                $this->log('[Redirect error page]' . $e->getMessage());
            }
        } catch (\Exception $e) {
            $this->log('[Original request]' . $e->getMessage());
        }
    }

    /**
     * catch异常时，选择进行记录日志，还是输出到页面中
     * 取决于APP_DEBUG
     * @param string $errorMessage
     */
    protected function log(string $errorMessage) {
        if (APP_DEBUG === false) {
            //错误日志记录
            (new Logger(LOG_PATH, LogLevel::DEBUG, ['extension' => 'log']))->error($errorMessage);
        }else {
            //调试状态下，错误信息输出
            echo $errorMessage . "<br>\n";
        }
    }

    /**
     * 删除敏感字符
     * from: fastPHP
     * @param $value
     * @return mixed
     */
    protected function stripSlashesDeep($value) {
        $value = is_array($value) ? array_map('stripSlashesDeep', $value) : stripslashes($value);
        return $value;
    }

    /**
     * 检测敏感字符并删除
     * from: fastPHP
     * */
    protected function removeMagicQuotes() {
        if (get_magic_quotes_gpc()) {
            $_GET = $this->stripSlashesDeep($_GET);
            $_POST = $this->stripSlashesDeep($_POST);
            $_COOKIE = $this->stripSlashesDeep($_COOKIE);
            $_SESSION = $this->stripSlashesDeep($_SESSION);
        }
    }

    /**
     * from: fastPHP
     * 检测自定义全局变量（register globals）并移除
     */
    protected function unregisterGlobals() {
        if (ini_get('register_globals')) {
            $array = array('_SESSION', '_POST', '_GET', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES');
            foreach ($array as $value) {
                foreach ($GLOBALS[$value] as $key => $var) {
                    if ($var === $GLOBALS[$key]) {
                        unset($GLOBALS[$key]);
                    }
                }
            }
        }
    }

    /**
     * 设置错误输出
     * 摘自：fastPHP
     * */
    protected function setEnvDebug() {
        if (APP_DEBUG === true) {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(E_ALL);
            ini_set('display_errors', 'Off');
            ini_set('log_errors', 'On');

            $error_log = LOG_PATH . '/error.log';
            if (!file_exists($error_log)) {
                touch($error_log);
            }
            ini_set('error_log', $error_log);
        }
    }

    /**
     * 设置环境
     * 如：
     *  (1) 网页编码
     *  (2) 时区
     * */
    public function setEnv() {
        $this->setEnvDebug();

        ini_set('default_charset', $this->HTMLEncode);
        ini_set('date.timezone', $this->timezone);
        ini_set('register_globals', 'off');
        ini_set('allow_url_include', 'off');
    }

    /**
     * 配置文件存在可能路径
     *  注：
     *      写在下面的会覆盖前面的（如果有的话）
     * @return array
     * */
    public function configPath() {
        return [
            APP_COMMON_PATH . '/config',
            APP_ENV_PATH . '/config',
        ];
    }

    /**
     * 获取fileName对应文件，在项目中的路径
     *
     * 注：
     *  (1) 路径搜索范围由function configPath()指定
     *  (2) 文件优先级：项目文件夹/config/xxx.php > web目录/common/config/xxx.php
     *
     * @param string $fileName     搜索文件名
     * @param bool $isCover 是否只使用优先级最高的那个文件路径
     * @return array        文件路径组
     */
    protected function getExistedConfigPaths(string $fileName, $isCover = false) {
        $paths = [];
        if ($isCover) {
            foreach (array_reverse($this->configPath()) as $path) {
                $file = $path . '/' . $fileName . '.php';
                if (file_exists($file)) {
                    $paths = [$file];
                    break;
                }
            }
        } else {
            foreach ($this->configPath() as $path) {
                $file = $path . '/' . $fileName . '.php';
                if (file_exists($file)) {
                    array_push($paths, $file);
                }
            }
        }
        return $paths;
    }

    /**
     * 从文件中获取当前环境下的配置设置
     *
     * 含bug
     *
     * 参数解释同上
     * @param string $fileName
     * @param bool $isCover
     * @return array
     */
    protected function loadConfig(string $fileName = 'config', $isCover = false) {
        $config = [];
        $paths = $this->getExistedConfigPaths($fileName, $isCover);
        foreach ($paths as $path) {
            foreach (require($path) as $key => $configArray) {
                $config[$key] = [];
                /*
                 * 检测是否含有键APP_ENV => 运行项目环境
                 * 含有，返回该键的值
                 * 没有，返回整个文件
                 *
                 * bug点：
                 * 使用者配置文件中APP_ENV拼写错误之类的情况，会返回所有配置，这不是所希望的
                 * by 2017-02-21 17:27
                 * */
                if (is_array($configArray) && array_key_exists(APP_ENV, $configArray)) {
                    $config[$key] = array_merge($config[$key], $configArray[APP_ENV]);
                }else {
                    $config[$key] = $configArray;
                }
            }
        }
        return $config;
    }

    /**
     * @return mixed
     */
    public function getCookie() {
        return $this->cookie;
    }

    /**
     * @return mixed
     */
    public function getSession() {
        return $this->session;
    }

    /**
     * @return string
     */
    public function getSingletonTraitName(): string {
        return $this->singletonTraitName;
    }

    /**
     * @return mixed
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @return array
     */
    public function getMessage(): array {
        return $this->message;
    }

    /**
     * @return array
     */
    public function getConfig(): array {
        return $this->config;
    }

    /**
     * @return array
     */
    public function getRouteConfig(): array {
        return $this->routeConfig;
    }

    /**
     * @return array
     */
    public function getViewMap(): array {
        return $this->viewMap;
    }
}