<?php
/**
 * Author: Alrash
 * Date: 2017/02/18 16:46
 * Description: 视图类，用于渲染
 */

namespace Aria\base;

use Aria\algorithm\Queue;

class View extends Component implements ViewInterface{
    use SetMethodLimitTrait;

    private $view = null;
    private $page = null;
    private $single = false;

    private static $data;

    private static $viewUnit;
    private static $viewUnitByName = [];
    private static $viewMap = [];
    private $useUnit = false;

    /**
     * 渲染接口
     * 分配如何渲染
     */
    public function render() {
        // TODO: Implement render() method.

        if ($this->useUnit === true) {
            //使用viewMap渲染
            self::renderNextByAlias('index');
        }elseif ($this->single === true) {
            //单独文件渲染
            include($this->getExistedPath($this->view . '/' . $this->page));
        }else {
            //包含header.php和footer.php文件进行渲染

            //获取header.php与footer.php文件位置
            $header = $this->getExistedPath($this->view . '/header', false);
            $header = isset($header) ? $header : $this->getExistedPath('header');
            $footer = $this->getExistedPath($this->view . 'footer', false);
            $footer = isset($footer) ? $footer : $this->getExistedPath('/footer');

            include($header);
            include($this->getExistedPath($this->view . '/' . $this->page));
            include($footer);
        }
    }

    /**
     * 组件拼接渲染
     * 需要包含的地方，调用
     *
     * 比较烂的地方
     */
    public static function renderNext() {
        // TODO: Implement renderNext() method.
        if (self::$viewUnit instanceof Queue) {
            $flag = true;

            /*
             * 寻找未被包含的组件
             * 存在含有重复值的bug by 2017.03.18
             * */
            do {
                if (self::$viewUnit->isEmpty()) {
                    $flag = false;
                }

                $top = self::$viewUnit->top();
                self::$viewUnit->pop();
            }while(!in_array($top['page'], self::$viewUnitByName));

            if ($flag === true) {
                //删除viewUnitByName数组[alias]键
                unset(self::$viewUnitByName[$top['alias']]);

                include($top['page']);
            }
        }
    }

    /**
     * @param $alias string 组件别名
     *
     * 组件渲染用
     *
     * 比较烂的地方
     */
    public static function renderNextByAlias(string $alias) {
        if (isset(self::$viewUnitByName[$alias])) {
            $page = self::$viewUnitByName[$alias];
            unset(self::$viewUnitByName[$alias]);
            include($page);
        }
    }

    /**
     * 设置数据
     * @param array $data
     * @throws LogicException
     */
    public function set(array $data) {
        // TODO: Implement set() method.
        foreach ($data as $key => $value) {
            if (!filter_var($key, FILTER_VALIDATE_INT)) {
                $this->setByName($key, $value);
            }else {
                throw new LogicException('Could not set ' . $key . ' as key!');
            }
        }
    }

    /**
     * 使用名字设置数据
     * @param string $name
     * @param $value
     */
    public function setByName(string $name, $value) {
        // TODO: Implement setByName() method.
        self::$data[$name] = $value;
    }

    /**
     * 获取所有数据
     * @return array
     */
    public static function get(): array {
        // TODO: Implement get() method.
        return self::$data;
    }

    /**
     * 获取数据，通过数据名
     * @param string $name
     * @return null
     */
    public static function getByName(string $name) {
        // TODO: Implement getByName() method.
        if (array_key_exists($name, self::$data)) {
            return self::$data[$name];
        }
        return null;
    }

    /**
     * 获取文件路径
     * 不存在时返回null
     *
     * @param string $path
     * @param bool $throw               是否需要抛出异常
     * @return null|string
     * @throws FileNotFoundException
     */
    protected function getExistedPath(string $path, bool $throw = true) {
        $realPath = APP_VIEW_PATH . '/' . $path . '.php';
        if (file_exists($realPath)) {
            return $realPath;
        }elseif (($realPath = APP_COMMON_VIEW_PATH . '/' . $path . '.php') && file_exists($realPath)) {
            return $realPath;
        }elseif ($throw === true) {
            throw new FileNotFoundException('Could not found file ' . $path . '.php in ' . APP_VIEW_PATH . ' and ' . APP_COMMON_VIEW_PATH . '!');
        }

        return null;
    }

    /**
     * 重载paramsMap函数
     * 调整config => viewMap
     * @param array $params
     * @return array
     * */
    public function paramsMap(array $params = []): array {
        return [
            'config' => 'viewMap',
        ];
    }

    /**
     * @return string
     */
    public function getPage(): string {
        return $this->page;
    }

    /**
     * @return string
     */
    public function getView(): string {
        return $this->view;
    }

    /**
     * @param string $page
     */
    public function setPage(string $page) {
        $this->page = $page;
    }

    /**
     * @param string $view
     */
    public function setView(string $view) {
        $this->view = $view;
    }

    /**
     * @param boolean $single
     */
    public function setSingle(bool $single) {
        $this->single = $single;
    }

    /**
     * @param array $viewMap
     */
    protected function setViewMap(array $viewMap) {
        self::$viewMap = $viewMap;
    }

    /**
     * @param string $index
     * @throws MissingException
     * @throws FileNotFoundException
     */
    public function setViewUnit(string $index) {
        if (array_key_exists($index, self::$viewMap)) {
            self::$viewUnit = new Queue();
            $viewUnit = self::$viewMap[$index];

            //防止缺失index
            if (!array_key_exists('index', $viewUnit)) {
                throw new MissingException('view unit ' . $index . ' missing index key');
                return;
            }

            //移除index标签
            self::$viewUnitByName['index'] = $this->getExistedPath($viewUnit['index']);
            unset($viewUnit['index']);

            foreach ($viewUnit as $alias => $path) {
                $real = $this->getExistedPath($path);
                self::$viewUnit->push(['alias' => $alias, 'page' => $real]);
                //添加别名用数组
                if (is_string($alias)) {
                    self::$viewUnitByName[$alias] = $real;
                }
            }

            $this->useUnit = true;
        }else {
            throw new MissingException('View Map does not have key ' . $index);
        }
    }
}