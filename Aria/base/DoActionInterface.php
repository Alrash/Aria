<?php
/**
 * Author: Alrash
 * Date: 2017/02/17 21:58
 * Description: 调用动作接口
 */

namespace Aria\base;

interface DoActionInterface {
    public function doAction(string $class, string $action, $params = []);
}