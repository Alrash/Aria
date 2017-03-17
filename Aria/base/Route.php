<?php
/**
 * Author: Alrash
 * Date: 2017/02/07 23:24
 * Description: 路由检测及替换
 */

namespace Aria\base;

use Aria\Aria;

/**
 * Class Route
 * @package Aria\base
 *
 * 注：
 *  (1) 可优化（目测在检测与填充那）
 *  (2) 可增加默认选项，如id，可被替换成\d+或者[1-9]\d*之类
 *  (3) 需要解决原始路由获取问题，现在太耦合
 */
class Route extends Component {
    private $rules = [];
    private $suffix;
    private $pretty = false;
    private $errorRoute = '404';
    private $originRoute;

    /**
     * 获取转换后的路由
     *  注：
     *      (1) 若配置中url_pretty = false/0，则直接返回去除假后缀的路由
     * @return string 真实路由
     */
    public function getRealRoute(): string {
        if ($this->pretty == false || $this->rules === [] || $this->originRoute === '/') {
                return $this->getOriginRoute();
        }

        $targetRoute = null;
        foreach ($this->rules as $originRule => $targetRule) {
            switch ($originRule) {
                /*
                 * '*': 表示任意路由
                 * 'deny': 表示禁止任意路由
                 * 这两个均指向规则中对应值，如果是{origin}，则原样取出
                 * */
                case '*':
                    if ($targetRule === '{origin}') {
                        $targetRoute = $this->getOriginRoute();
                    }else {
                        $targetRoute = $targetRule;
                    }
                    $isBreak = true;
                    break;
                case 'deny':
                    $targetRoute = $targetRule;
                    $isBreak = true;
                    break;
                //正则匹配检测，并填充
                default:
                    $targetRoute = $this->adjustRoute($originRule, $targetRule, $this->originRoute);
                    $isBreak = $targetRoute === false ? false : true;
                    break;
            }

            if ($isBreak){
                break;
            }
        }

        //$targetRoute === null / false
        if (empty($targetRoute)) {
            $targetRoute = $this->errorRoute;
        }

        return $targetRoute;
    }

    /**
     * @param string $originRule 原始规则
     * @param string $targetRule 目标规则
     * @param string $route 请求路由
     * @return bool|string 匹配成功，返回转换后的路由；失败时，返回false
     */
    private function adjustRoute($originRule, $targetRule, $route){
        $params = [];
        //第一遍调整规则，变为/<paramName:regex>的形式
        $originRule = $this->adjustOriginRouteRule($originRule);

        //第二遍调整规则，变为/(regex)的形式(/<$1:index>/<action:\w+>/<$2:pc> => /(index)/(\w+)/(pc))
        $originRule = preg_replace_callback('/\/(<([^:]+):([^>]+)>)/i',
                function($matches) use (&$params) {
                    //重要的是值，而不是键，未来会将值转化成键
                    $params[$matches[2]] = $matches[2];
                    return '/(' . $matches[3] . ')';
                },
                $originRule);
        //添加/^$/i标记，添加虚假后缀
        //$originRule = '/^' . str_replace('/', '\/', $originRule) . $this->suffix . '$/i';
        $originRule = '/^' . str_replace('/', '\/', $originRule);
        if ($originRule[strlen($originRule) - 1] != '/') {
            $originRule .= '.' . $this->suffix;
        }
        $originRule .= '$/i';

        /*
         * $matches存放匹配结果，$matches[0]存放整个路径 = route，不需要
         * ***preg_match 匹配成功返回1***
         * */
        if (preg_match($originRule, $route, $matches) === 1){
            unset($matches[0]);
            $params = array_combine($params, $matches);
            //填充变量<name>，并然后
            return preg_replace_callback('/\/<([^>]+)>/i',
                function($matches) use ($params) {
                    if (isset($params[$matches[1]])){
                        return '/' . $params[$matches[1]];
                    }else{
                        return '/' . $matches[1];
                    }
                },
                $targetRule
                );
        }else{
            return false;
        }
    }

    /**
     * 调整匹配规则：
     *  原规则基本为/<name:regex>/const/...
     *  这个方法是将const变化调整为<$?:const>
     * @param $rule string
     * @return string
     */
    private function adjustOriginRouteRule($rule): string {
        $count = 0;
        return preg_replace_callback('/\/([a-z][a-z0-9]*)/i',
            function ($matches) use (&$count) {
                return '/<$' . ++$count . ':' . $matches[1] . '>';
            },
            $rule);
    }

    protected function setConfig($config){
        @$this->setPretty($config['url_pretty']);
        @$this->setSuffix($config['suffix']);
        @$this->setRules($config['rule']);
        @$this->setOriginRoute(Aria::$app->request->server['REQUEST_URI'], Aria::$app->request->get['route']);
    }

    private function setRules($rules) {
        if (isset($rules)) {
            if (isset($rules['error'])) {
                $this->errorRoute = $rules['error'];
                unset($rules['error']);
            }
        }else {
            $rules = [];
        }
        $this->rules = $rules;
    }

    /**
     * @param $pretty
     */
    private function setPretty($pretty) {
        if (isset($pretty)) {
            $this->pretty = $pretty === true ? true : false;
        }else {
            $this->pretty = false;
        }
    }

    /**
     * @param string $suffix
     */
    private function setSuffix($suffix) {
        $this->suffix = isset($suffix) ? $suffix : '';
        $this->suffix = ltrim($this->suffix, '.');
    }

    /**
     * @param string $fromServer
     * @param string $fromGet
     */
    private function setOriginRoute($fromServer, $fromGet) {
        if ($this->pretty){
            $this->originRoute = explode('?', $fromServer)[0];
        }else {
            $this->originRoute = isset($fromGet) ? $fromGet : '/';
        }
    }

    /**
     * @return string
     * */
    public function getErrorRoute(): string {
        return $this->errorRoute;
    }

    /**
     * @return array
     */
    public function getRules(): array {
        $route = [];
        foreach ($this->rules as $origin => $target){
            $route[htmlspecialchars($origin)] = htmlspecialchars($target);
        }
        return $route;
    }

    /**
     * @return string
     */
    public function getOriginRoute(): string {
        return preg_replace('/\.' . $this->suffix . '$/i', '', $this->originRoute);
    }

    /**
     * @return string
     */
    public function getSuffix():string {
        return $this->suffix;
    }

    /**
     * @return boolean
     */
    public function isPretty(): bool {
        return $this->pretty;
    }
}