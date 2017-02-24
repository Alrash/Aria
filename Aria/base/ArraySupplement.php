<?php
/**
 * Author: Alrash
 * Date: 2017/01/24 17:05
 * Description: 扩展数组
 */

namespace Aria\base;

trait ArraySupplement {

    /**
     * 取出数组中的键为$keyName的数组中的值
     * 如：$arr = [['key' => 'hello'], ['no' => 'ohana','key' => 'world'], ['system' => 'arch']]
     *  返回值为['hello', 'world']
     * 额。。。就是array_column
     * @param array $arr
     * @param $keyName
     * @return array
     */
    protected function array_escape_key(array $arr, $keyName) {
        $array = [];
        foreach ($arr as $item) {
            if (isset($item[$keyName]))
                array_push($array, $item[$keyName]);
        }
        return $array;
    }

    /**
     * 值计数
     * @param array $arr
     * @param $keyName
     * @return int
     */
    protected function array_count_value(array $arr, $keyName) {
        $counts = 0;
        foreach ($arr as $item) {
            if ($item === $keyName) {
                $counts++;
            }
        }

        return $counts;
    }
}