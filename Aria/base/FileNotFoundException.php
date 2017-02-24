<?php
/**
 * Author: Alrash
 * Date: 2017/01/17 13:00
 * Description: 文件未发现类
 */

namespace Aria\base;

class FileNotFoundException extends \Exception {
    public function __construct($message, $code = 0, Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }

    public function getExceptionName() {
        return "File Not Found";
    }
}