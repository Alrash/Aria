<?php
/**
 * Author: Alrash
 * Date: 2017/01/25 13:16
 * Description:
 */

return [
    'verificationError' => [
        'format' => [
            'username' => [
                'zh-CN' => '用户名长度需为6-18位，且不能含特殊字符',
                'en' => 'Length of username must be between 6 with 18, and don\'t contain special characters!',
            ],
            'id' => [
                'zh-CN' => 'id只能使用数字表示！',
                'en' => 'ID must be numerical!',
            ],
            'email' => [
                'zh-CN' => '邮箱格式错误！',
                'en' => 'Email format error!',
            ],
            'regex' => [
                'zh-CN' => '正则不匹配！',
                'en' => ' regex error!',
            ],
            'string' => [
                'zh-CN' => '需要字符形式',
                'en' => 'need string type!',
            ],
            'boolean' => [
                'zh-CN' => '需要布尔值形式',
                'en' => 'need boolean type!',
            ],
            'int' => [
                'zh-CN' => '需要数字形式',
                'en' => 'need int type!',
            ],
            'float' => [
                'zh-CN' => '需要浮点数形式',
                'en' => 'need float type!',
            ],
            'error' => [
                'zh-CN' => '格式错误',
                'en' => 'Format error!',
            ],
        ],
        'missing' => [
            'username' => [
                'zh-CN' => '用户名必须填写！',
                'en' => 'Must fill out username field!',
            ],
            'email' => [
                'zh-CN' => '邮箱必须填写！',
                'en' => 'Must fill out e-mail field!',
            ],
            'error' => [
                'zh-CN' => '缺失',
                'en' => 'Missing',
            ],
        ],
        'range' => [
            'id' => [
                'zh-CN' => 'ID范围错误！',
                'en' => 'Range of ID error!',
            ],
            'length' => [
                'zh-CN' => '长度范围错误',
                'en' => 'length error',
            ],
            'error' => [
                'zh-CN' => '范围出错！',
                'en' => 'Range error!',
            ],
        ],
        'union' => [
            'zh-CN' => '存在不唯一！',
            'en' => 'union error!',
        ],
        'compare' => [
            'zh-CN' => '比较不正确！',
            'en' => 'compare get false result!',
        ],
    ],
];