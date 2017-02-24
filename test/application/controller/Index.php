<?php
/**
 * Author: Alrash
 * Date: 2017/02/20 14:36
 * Description:
 */

namespace test\application\controller;

use Aria\base\Controller;

class Index extends Controller{
    public function index() {
        return $this->render('index', 'index', [], true);
    }

    public function test() {}
}