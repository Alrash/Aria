<?php
/**
 * Author: Alrash
 * Date: 2017/02/17 21:57
 * Description: 复用调用动作
 */

namespace Aria\base;

use Aria\stack\RepeatableStack;

trait DoActionTrait {
    use CallbackTrait;

    private static $stack = null;

    /**
     * 拆分路由信息
     * @param string $route
     * @param string $namespace 类所在命名空间
     * @return array
     */
    protected function splitRoute(string $route, string $namespace = APP_ENV . '\application\controller\\'){
        $routeArray = explode('/', trim($route, '/'));

        $class = empty($routeArray[0]) ? 'Index' : ucfirst($routeArray[0]);
        array_shift($routeArray);
        $action = empty($routeArray[0]) ? 'index' : $routeArray[0];
        array_shift($routeArray);
        $params = empty($routeArray) ? [] : $routeArray;
        $class =  $namespace . $class;

        return ['class' => $class, 'action' => $action, 'params' => $params];
    }

    /**
     * 调用class::action
     * @param string $class
     * @param string $action
     * @param array $params
     * @return mixed            调用方法返回值
     * @throws LogicException   防止重定向过多
     */
    public function doAction(string $class, string $action, $params = []) {
        // TODO: Implement doAction() method.
        //防止重定向过多
        if (!isset(self::$stack)) {
            self::$stack = new RepeatableStack(RepeatableStack::level_loose);
        }
        if (self::$stack->push([$class, $action, $params]) === false) {
            throw new LogicException('Redirect too much!');
        }

        //调用
        $response = $this->callbackReflectionParams($class, $action, $params, false);

        if ($response instanceof Response) {

            //需要重定向
            //获取重定向的类，动作和参数
            $redirect = $response->getTarget();
            if ($response->useOldController()) {
                //需要回来，也许可以不写

                //重定向
                $add = (new Response())->doAction($redirect['class'], $redirect['action'], $redirect['params']);

                if (isset($add)) {
                    if (!is_array($add)) {
                        $add = [$add];
                    }
                    $params =  $add;
                }

                $redirect = [
                    'class' => $class,
                    'action' => $action,
                    'params' => $params
                ];
            }
            //做回来的重定向
            if (isset($redirect['class']) && isset($redirect['action'])) {
                $this->doAction($redirect['class'], $redirect['action'], $redirect['params']);
            }

            //渲染页面
            if ($response->view instanceof View){
                $response->view->render();
            }

            self::$stack->pop();
        }else {
            self::$stack->pop();
            return $response;
        }
    }
}