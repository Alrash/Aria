<?php
/**
 * Author: Alrash
 * Date: 2017/02/11 15:20
 * Description:
 */

namespace Aria\base;

interface CookieInterface {
    public function set($name, $value, $time, $path);
    public function hash(string $value, string $salt): string;
    public function get(): array;
    public function getByName(string $name);
    public function hasCookie(string $name): bool;
}