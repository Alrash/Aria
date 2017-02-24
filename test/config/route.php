<?php
return [
    'url_pretty' => true,
    'suffix' => '.htm',
    'rule' => [
        '/<action:[^/]+>' => '/index/<action>',
        'deny' => '/error',
        'error' => '/error/action'
    ],
];