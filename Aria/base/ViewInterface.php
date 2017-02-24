<?php
/**
 * Author: Alrash
 * Date: 2017/02/18 16:47
 * Description:
 */

namespace Aria\base;

interface ViewInterface {
    public function render();
    public static function renderNext();

    public function setByName(string $name, $value);
    public function set(array $data);
    public static function get(): array ;
    public static function getByName(string $name);
}