<?php
/**
 * Author: Alrash
 * Date: 2017/01/26 21:35
 * Description: 通用初始化方法
 */

namespace Aria\base;

trait InitTrait {
    /**
     * 通用初始化方法
     *  注意：$params初始的属性需要set方法
     *
     * @param array $config
     * @param array $params
     * @throws
     */
    public function init(array $config = [], array $params = []) {
        if (!is_array($config) || !is_array($params))
            throw new ParamException('Need array parameter!');

        if (isset($this->paramsMap()['config'])) {
            $setConfig = 'set' . $this->paramsMap()['config'];
        } else {
            $setConfig = 'setConfig';
        }
        if ($this->hasMethod($setConfig)) {
            $this->$setConfig($config);
        }

        $this->adjustParamsMap($params);

        foreach ($params as $key => $param) {
            $setter = 'set' . $key;
            if ($this->hasMethod($setter)) {
                $this->$setter($param);
            }
        }
    }

    /**
     * 映射构造方法参数二中的key为类内真实属性名
     * params_key => propertyName
     *
     * @return array
     */
    public function paramsMap() {
        return [];
    }

    /**
     * 将$params中的键名调换成类内属性名
     *
     * @param array $params
     * @return mixed
     */
    protected function adjustParamsMap(array &$params) {
        $map = $this->paramsMap();

        if (isset($map['config'])) {
            unset($map['config']);
        }

        foreach ($map as $old_key => $new_key) {
            if (isset($params[$old_key])) {
                $params[$new_key] = $params[$old_key];
                unset($params[$old_key]);
            } else {
                trigger_error('Could not find key ' . $old_key . ' in class ' . get_called_class() . ' function paramsMap!', E_USER_WARNING);
            }
        }
    }
}