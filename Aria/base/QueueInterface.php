<?php
/**
 * Author: Alrash
 * Date: 2017/01/27 00:00
 * Description: 队列接口
 */

namespace Aria\base;

interface QueueInterface {
    public function pop();

    public function push($object);

    public function clear();

    public function top();

    public function isEmpty();
}