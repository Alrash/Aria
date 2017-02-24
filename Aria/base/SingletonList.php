<?php
/**
 * Author: Alrash
 * Date: 2017/01/31 20:03
 * Description:
 * 折中的办法保存单例模式名，方便callback时确认
 */

namespace Aria\base;

class SingletonList extends Object {
    private static $list = [];

    /**
     * @return array
     */
    public static function getList(): array {
        return self::$list;
    }

    public static function addList($object) {
        if (!is_string($object))
            throw new ParamException('The addList method of ' . __CLASS__ . ' class needs string type parameter!');

        if (!in_array($object, self::$list)) {
            array_push(self::$list, $object);
        }
    }

    public static function removeList($object) {
        $key = array_search($object, self::$list);
        if ($key) {
            unset(self::$list[$key]);
        }
    }
}