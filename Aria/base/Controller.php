<?php
/**
 * Author: Alrash
 * Date: 2017/01/23 12:52
 * Description: 项目中所有控制器的父类
 */

namespace Aria\base;

/**
 * Class Controller
 *
 * 重载的接口注释见接口文件
 *  很容易发现，实际上重定向抑或是渲染页面，均是利用Response类实现，并返回Response对象，自由扩展时，可以直接使用Response类
 * @package Aria\base
 */
class Controller extends Component implements ControllerInterface{

    public function redirect(string $controller = 'index', string $action = 'index', $params = [], bool $useOldController = false): Response {
        // TODO: Implement redirect() method.
        $response = new Response();
        $response->redirect($controller, $action, $params, $useOldController);
        return $response;
    }

    public function render(string $view = 'index', string $page = 'index', array $data, bool $single = false): Response {
        // TODO: Implement render() method.
        $response = new Response();
        $response->render($view, $page, $data, $single);
        return $response;
    }

    public function renderAsJson(array $data): Response {
        // TODO: Implement renderAsJson() method.
        $response = new Response();
        $response->renderAsJson($data);
        return $response;
    }

    public function renderAsXML(string $xmlString): Response {
        // TODO: Implement renderAsXML() method.
        $response = new Response();
        $response->renderAsJson($xmlString);
        return $response;
    }

    public function renderUseUnit(string $index, array $data = []): Response {
        // TODO: Implement renderUseUnit() method.
        $response = new Response();
        $response->renderUseUnit($index, $data);
        return $response;
    }
}