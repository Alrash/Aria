<?php
/**
 * Author: Alrash
 * Date: 2017/01/18 23:58
 * Description: 组件类，暂时没东西
 */

namespace Aria\base;

/**
 * Class Component
 * @package Aria\base
 */
class Component extends Object {
    use InitTrait;

    /**
     * Component constructor.
     * @param array $config
     * @param array $params
     */
    public function __construct(array $config = [], array $params = []) {
        $this->init($config, $params);
    }
}