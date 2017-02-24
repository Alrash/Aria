<?php
/**
 * Author: Alrash
 * Date: 2017/01/17 13:00
 * Description: 抛出未发现类错误
 * 命名等思想来自Yii2.0
 */

namespace Aria\base;

class UnknownClassException extends \Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getExceptionName() {
        return "Unknown Class";
    }
}