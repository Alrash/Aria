<?php
/**
 * Author: Alrash
 * Date: 2017/01/31 19:18
 * Description:
 */

namespace Aria\base;

use Aria\verification\Verification;

interface ModelInterface {
    public function rules();

    public function load(array $data, $scenario = Verification::default_scenario);
}