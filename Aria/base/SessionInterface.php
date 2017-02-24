<?php
/**
 * Author: Alrash
 * Date: 2017/02/11 15:20
 * Description:
 */

namespace Aria\base;

interface SessionInterface {
    public function open();
    public function prevent(): bool;
    public function set($name, $value);
    public function get(): array;
    public function getByName($name);
    public function hasSession($name): bool;
}