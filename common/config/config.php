<?php
/**
 * Author: Alrash
 * Date: 2017/01/16 23:06
 * Description: 配置文件
 *
 * 使用注意：
 *  （1）数据库仅使用mysql/mariaDB，使用pdo_mysqli.so扩展
 *  （2）基本配置如下：
 *      ```php
 *      return [
 *          #configName -- 配置名，如database，获取配置方法为Aria::$app->config['database']
 *          'configName-one' => [
 *              'property-one' => '',
 *              'property-two' => '',
 *              'property-three' => '',
 *              ...
 *          ],
 *          'configName-two' => [
 *              'property-one' => '',
 *              'property-two' => '',
 *              'property-three' => '',
 *              ...
 *          ],
 *          ...
 *      ];
 *      ```
 *      如果是多个配置，请使用如下写法：
 *      ```php
 *      return [
 *          'configName-one' => [
 *              #env由xx（非common文件夹）/config/setting.php中的APP_ENV决定，一般是当前项目xx目录名（非根目录）
 *              'env_one' => [
 *                  'property-one' => '',
 *                  'property-two' => '',
 *                  'property-three' => '',
 *                  ...
 *              ],
 *              'env_two' => [
 *                  ...
 *              ],
 *          ],
 *          'configName-two' => [],
 *      ];
 *      ```
 *
 * 注：
 *  (1) 带环境的配置区分加载在Aria/base/BaseApp.php中loadConfig($name)中实现
 */

return [
    //配置数据库
    'database' => [
        'test' => [
            'dbName'   => 'dbName',
            'user'     => 'userName',
            'password' => 'password',
            'host'     => 'localhost',
            'port'     => '3306',
        ],
    ],
    /*
     * 配置session
     * path, lefttime, name均可选
     * */
    'session' => [
        'test' => [
            'path'    => '/tmp/www/frontEnd',
            'lefttime' => 144400,
            'name'    => 'SESSID',
        ],
    ],
    //配置cookie
    'cookie' => [
        'test' => [
            'auth_key' => 'Please your unique phrase here! It\'s necessary!',
            'auth_iv' => 'Please your unique phrase here! It\'s necessary!',
            'additional_auth_data' => 'Please your unique phrase here! It\'s necessary!',
            'auth_key_salt' => 'Please your unique phrase here! It\'s necessary!',
            'login_salt' => 'Please your unique phrase here! It\'s necessary!',
        ],
    ],
];