<?php
/**
 * Author: Alrash
 * Date: 2017/01/21 22:57
 * Description:
 */

namespace Aria\verification;

interface VerifyRulesInterface {
    public function verifyRules(array $rules, array $data, $scenario);
}