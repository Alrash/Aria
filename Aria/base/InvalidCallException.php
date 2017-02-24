<?php
/**
 * Author: Alrash
 * Date: 2017/01/17 13:05
 * Description: 抛出忽略调用异常
 * 命名等思想来自Yii2.0
 */

namespace Aria\base;

class InvalidCallException extends \Exception {
    public function getExceptionName() {
        return 'Invalid Call';
    }
}