<?php
/**
 * Author: Alrash
 * Date: 2017/02/23 20:02
 * Description:
 */

namespace Aria\db;

use Aria\Aria;

class DB extends DataBase{
    public function __construct() {
        parent::__construct(Aria::$app->config['database']);
    }
}