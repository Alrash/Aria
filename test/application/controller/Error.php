<?php
/**
 * Author: Alrash
 * Date: 2017/02/24 23:08
 * Description:
 */

namespace test\application\controller;

use Aria\Aria;
use Aria\base\Controller;

class Error extends Controller{
    public function index() {
        return $this->renderUseUnit('notFoundAction', ['route' => Aria::$app->request->originRoute]);
    }

    public function action() {
        echo 'do function ' . __CLASS__ . '::action<br>';
        echo 'redirect to /<br>';
        return $this->redirect('Index');
    }
}