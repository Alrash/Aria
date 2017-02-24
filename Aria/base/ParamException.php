<?php
/**
 * Author: Alrash
 * Date: 2017/01/23 18:56
 * Description: 参数错误
 */

namespace Aria\base;

class ParamException extends \Exception {
    public function getExceptionName() {
        return 'Param';
    }
}