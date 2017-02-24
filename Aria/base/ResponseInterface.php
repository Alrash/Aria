<?php
/**
 * Author: Alrash
 * Date: 2017/02/17 22:13
 * Description:
 */

namespace Aria\base;

interface ResponseInterface extends DoActionInterface{
    public function render(string $view, string $page, array $data, bool $single = false);
    public function renderAsJson(array $data);
    public function renderAsXML(string $xmlString);
    public function renderUseUnit(string $page, array $data);
}