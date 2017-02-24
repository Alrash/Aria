<?php
/**
 * Author: Alrash
 * Date: 2017/01/27 20:40
 * Description: 栈
 * 是对数组操作，较于已有的数组方法，更加直观
 */

namespace Aria\algorithm;

use Aria\base\Object;
use Aria\base\StackInterface;

class Stack extends Object implements StackInterface {
    private $stack = [];

    public function __construct() {
        $this->stack = [];
    }

    public function pop() {
        // TODO: Implement pop() method.
        if (!$this->isEmpty())
            array_pop($this->stack);
    }

    public function push($object) {
        // TODO: Implement push() method.
        array_push($this->stack, $object);
    }

    public function top() {
        // TODO: Implement top() method.
        return end($this->stack);
    }

    public function clear() {
        // TODO: Implement clear() method.
        $this->stack = [];
    }

    public function isEmpty() {
        // TODO: Implement isEmpty() method.
        return $this->stack === [];
    }

    /**
     * @return array
     */
    protected function getStack() {
        return $this->stack;
    }
}