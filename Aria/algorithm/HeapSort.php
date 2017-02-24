<?php
/**
 * Author: Alrash
 * Date: 2017/01/24 12:27
 * Description: 堆排序
 */

namespace Aria\algorithm;

use Aria\base\MissingException;
use Aria\base\Object;

class HeapSort extends Object {
    const MAX_HEAP = 'cmp_down';
    const MIN_HEAP = 'cmp_up';
    const DEFAULT_KEY_NAME = 'heapSort';

    private $heap = [];

    /*
     * 描述堆的特征
     * $isArray 堆内元素是否为数组，不是数组，转换成数组（获取元素/堆时，还原）
     * $keyName 待比较值的健
     * $cmp_func 创建最大堆/还是最小堆
     * */
    private $isArray;
    private $keyName;
    private $cmp_func;

    /**
     * HeapSort constructor.
     * @param bool $isArray
     * @param string $keyName
     * @param string $default_sort
     */
    public function __construct($isArray = false, $keyName = self::DEFAULT_KEY_NAME, $default_sort = self::MAX_HEAP) {
        $this->isArray = $isArray;
        $this->keyName = $keyName;
        $this->cmp_func = $default_sort;
    }

    /**
     * 创建堆
     * 使用已有数组创建，会清除已有堆，但是原本堆特性不变
     * @param array $arr
     * */
    public function make(array $arr) {
        $this->clear();
        foreach ($arr as $value)
            $this->push($value);
    }

    /*
     * 增加元素
     * */
    public function push($node) {
        if (!$this->isArray) {
            $node = [$this->keyName => $node];
        }

        if (!array_key_exists($this->keyName, $node)) {
            throw new MissingException('Array miss key ' . $this->keyName . '.');
        }

        array_push($this->heap, $node);
        $this->up(count($this->heap) - 1);
    }

    /*
     * 弹出堆首元素
     * */
    public function pop() {
        if ($this->isEmpty())
            return;

        if (count($this->heap) !== 1) {
            $this->heap[0] = array_pop($this->heap);
            $this->down(0, count($this->heap) - 1);
        } else {
            array_pop($this->heap);
        }
    }

    /*
     * 上浮
     * */
    protected function up($i) {
        $func = $this->cmp_func;
        $p = floor(($i - 1) / 2);
        while ($p >= 0 && $i > 0
            && $this->$func($this->heap[$i][$this->keyName], $this->heap[$p][$this->keyName])) {
            $this->swap($this->heap, $i, $p);
            $i = $p;
            $p = floor(($i - 1) / 2);
        }
    }

    /*
     * 下沉
     * */
    protected function down($start, $end) {
        $func = $this->cmp_func;
        $l = 2 * $start + 1;
        while ($l <= $end) {
            if ($l + 1 <= $end
                && $this->$func($this->heap[$l + 1][$this->keyName], $this->heap[$l][$this->keyName])
            )
                $l++;
            if ($this->$func($this->heap[$start][$this->keyName], $this->heap[$l][$this->keyName]))
                break;
            $this->swap($this->heap, $start, $l);
            $start = $l;
            $l = 2 * $start + 1;
        }
    }

    /**
     * 返回堆首元素
     * @return mixed|null 如果堆为空，则返回null
     * */
    public function top() {
        if ($this->isEmpty()) {
            return null;
        }

        if ($this->isArray) {
            return $this->heap[0];
        } else {
            return $this->heap[0][$this->keyName];
        }
    }

    protected function swap(array &$array, $i, $j) {
        $tmp = $array[$i];
        $array[$i] = $array[$j];
        $array[$j] = $tmp;
    }

    /*
     * 比较两个值的大小，前大后小返回false，否则为true
     * */
    private function cmp_up($a, $b) {
        return $a <= $b;
    }

    /*
     * 比较两个值的大小，前小后大返回false，否则为true
     * */
    private function cmp_down($a, $b) {
        return $a >= $b;
    }

    public function isEmpty() {
        return $this->heap === [];
    }

    public function clear() {
        $this->heap = [];
    }

    /**
     * @return mixed
     */
    public function getHeap() {
        if (is_array())
            return $this->heap;
        else {
            return array_column($this->heap, $this->keyName);
            //return $this->array_escape_key($this->heap, $this->keyName);
        }
    }
}
