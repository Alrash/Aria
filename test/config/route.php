<?php
return [
    'url_pretty' => true,
    'suffix' => 'htm',
    'rule' => [
        '/<control:[^/]+>/' => '/<control>',
        '/<action:[^/]+>' => '/index/<action>',
        'deny' => '/error/action',
        '*' => '{origin}',
        'deny' => '/error',
        'error' => '/error/action'
    ],
];