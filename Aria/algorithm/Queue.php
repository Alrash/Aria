<?php
/**
 * Author: Alrash
 * Date: 2017/01/26 23:59
 * Description: 队列
 * 是对数组操作，较于已有的数组方法，更加直观
 */

namespace Aria\algorithm;

use Aria\base\Component;
use Aria\base\QueueInterface;

/**
 * Class Queue
 * @package Aria\algorithm
 */
class Queue extends Component implements QueueInterface {
    /**
     * @var array
     */
    private $queue = [];

    /**
     *
     */
    public function pop() {
        // TODO: Implement pop() method.
        if (!$this->isEmpty())
            array_shift($this->queue);
    }

    /**
     * @param $object
     */
    public function push($object) {
        // TODO: Implement push() method.
        array_push($this->queue, $object);
    }

    /**
     *
     */
    public function clear() {
        // TODO: Implement clear() method.
        $this->queue = [];
    }

    /**
     * @return mixed
     */
    public function top() {
        // TODO: Implement top() method.
        if ($this->isEmpty()) {
            return null;
        }
        return current($this->queue);
    }

    /**
     * @return bool
     */
    public function isEmpty() {
        // TODO: Implement isEmpty() method.
        return $this->queue === [];
    }
}