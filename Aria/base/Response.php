<?php
/**
 * Author: Alrash
 * Date: 2017/02/17 16:22
 * Description: 响应类 主要负责控制器与视图的交互 和 请求的转发
 */

namespace Aria\base;

use Aria\Aria;

class Response extends Component implements ResponseInterface{
    use DoActionTrait;

    //转发用
    private $target = ['class' => null, 'action' => null, 'params' => []];
    //本来是有两个数组（和$target一样），用于参数检测
    private $checkArray = ['class' => 'is_string', 'action' => 'is_string', 'params' => 'is_array'];
    //转发用，转发后再跳转回来（本没有，先写着了）
    private $hasOldController = false;

    //视图对象
    private $_view;

    /**
     * 转发、重定向
     * @param string $controller        跳转到的控制器
     * @param string $action            所调用方法
     * @param array $params             该方法的参数
     * @param bool $useOldController    结束要不要回来
     */
    public function redirect(string $controller = 'index', string $action = 'index', $params = [], bool $useOldController = false) {
        $this->hasOldController = $useOldController;

        if (!is_array($params)) {
            $params = [$params];
        }
        $this->setTarget(['class' => $controller, 'action' => $action, 'params' => $params]);
    }

    /**
     * 渲染视图用
     * @param string $view  视图组（文件夹名）
     * @param string $page  页面名（该文件夹下的一个文件）
     * @param array $data   使用数据
     * @param bool $single  是否包含header.php和footer.php
     */
    public function render(string $view = 'index', string $page = 'index', array $data, bool $single = false) {
        // TODO: Implement render() method.
        $this->_view = new View();

        $this->_view->setView($view);
        $this->_view->setPage($page);
        $this->_view->setSingle($single);
        $this->_view->set($data);

        header('Content-type: text/html; charset=utf-8');
    }

    /**
     * 渲染json数据格式页面
     * @param array $data
     */
    public function renderAsJson(array $data) {
        // TODO: Implement renderAsJson() method.
        $data = json_encode($data);
        $this->render('Json', 'index', ['json' => $data], true);

        header('Content-type: application/json; charset=utf-8');
    }

    /**
     * 渲染xml数据格式页面
     * @param string $xmlString
     */
    public function renderAsXML(string $xmlString) {
        // TODO: Implement renderAsXML() method.
        $this->render('XML', 'index', ['xml' => $xmlString], true);

        header('Content-type: application/xml; charset=utf-8');
    }

    /**
     * 使用viewMap中设置的视图组渲染
     * @param string $pageIndex
     * @param $data
     */
    public function renderUseUnit(string $pageIndex, array $data) {
        // TODO: Implement renderUseUnit() method.
        $this->_view = new View(Aria::$app->viewMap);

        $this->_view->setViewUnit($pageIndex);
        $this->_view->set($data);
    }

    public function useOldController(): bool {
        return $this->hasOldController;
    }

    /**
     * @return array
     */
    public function getTarget(): array {
        return $this->target;
    }

    /**
     * @return string
     */
    public function getPage(): string {
        if (!isset($this->page) || $this->page === '') {
            $this->page = 'index';
        }
        return $this->page;
    }

    /**
     * @return mixed
     */
    public function getView() {
        return $this->_view;
    }

    /**
     * 设置控制器信息
     * 实际上是设置目标控制器名，动作名与动作参数
     * @param array $redirect
     * @param array $target
     * @throws ParamException
     */
    private function setController(array $redirect, array &$target) {
        $diff = array_diff_key($target, $redirect);
        if ($diff === [] || array_keys($diff) === ['params']) {
            //删除无用的键值对
            $diff = array_diff_key($redirect, $target);
            foreach ($diff as $key => $value) {
                unset($redirect[$key]);
            }

            //赋值
            foreach ($redirect as $key => $value) {
                if ($this->checkArray[$key]($value)) {
                    $target[$key] = $value;
                }else {
                    throw new ParamException(ucfirst($key) . ' needs ' . explode('_', $this->checkArray[$key])[1] . ' type!');
                }
            }

            //处理class值，添加命名空间
            if (strpos($target['class'], '\\') === false) {
                $target['class'] = APP_ENV . '\application\controller\\' . $target['class'];
            }
        }else {
            throw new ParamException('Need key => value: class and action!');
        }
    }

    /**
     * @param array $target
     */
    public function setTarget(array $target) {
        $this->setController($target, $this->target);
    }
}