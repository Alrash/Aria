<?php
/**
 * Author: Alrash
 * Date: 2017/01/23 18:36
 * Description: 越界错
 */

namespace Aria\base;

class OverageException extends \Exception {
    public function getExceptionName() {
        return 'Overage Exception';
    }
}