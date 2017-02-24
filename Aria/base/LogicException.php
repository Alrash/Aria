<?php
/**
 * Author: Alrash
 * Date: 2017/01/17 13:00
 * Description: 逻辑错误，一般处理的是参数形式和变量类型错
 */

namespace Aria\base;

class LogicException extends \Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getExceptionName() {
        return "Logic";
    }
}