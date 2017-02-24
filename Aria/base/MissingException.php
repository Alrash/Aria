<?php
/**
 * Author: Alrash
 * Date: 2017/01/23 18:56
 * Description: 缺失错误，一般是配置的缺失
 */

namespace Aria\base;

class MissingException extends \Exception {
    public function getExceptionName() {
        return 'Missing';
    }
}