<?php
/**
 * Author: Alrash
 * Date: 2017/02/12 22:04
 * Description: Security类抛出的异常
 */

namespace Aria\base;

class SecurityException extends \Exception{
    public function getExceptionName() {
        return 'Security Exception';
    }
}