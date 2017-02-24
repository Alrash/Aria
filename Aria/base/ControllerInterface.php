<?php
/**
 * Author: Alrash
 * Date: 2017/02/22 00:15
 * Description: 控制器接口
 */

namespace Aria\base;

interface ControllerInterface {

    /**
     * 重定向
     * @param string $controller        需要的控制器，可使用完整命名空间
     * @param string $action            动作名
     * @param $params                   动作使用参数
     * @param bool $useOldController    是否重新调用自身方法
     * @return Response                 返回相应对象（至少本版本是这样）
     */
    public function redirect(string $controller, string $action , $params, bool $useOldController);

    /**
     * 通用渲染
     * @param string $view              使用的视图类别总称
     * @param string $page              使用的这个类别下的哪个页面
     * @param array $data               数据
     * @param bool $single              使用使用header与footer页
     * @return Response
     */
    public function render(string $view, string $page, array $data, bool $single);

    /**
     * Json格式渲染
     * @param array $data
     * @return Response
     */
    public function renderAsJson(array $data);

    /**
     * XML格式渲染
     * @param string $xmlString
     * @return Response
     */
    public function renderAsXML(string $xmlString);

    /**
     * 使用viewMap进行渲染
     * @param string $index             viewMap中的下标
     * @param array $data
     * @return Response
     */
    public function renderUseUnit(string $index, array $data);
}