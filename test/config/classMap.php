<?php
/**
 * Author: Alrash
 * Date: 2017/01/17 22:33
 * Description: 类名映射，存放非正常定义类类名与文件实际路径
 *  如：
 *      在Aria\base 下定义了一个测试类A，文件名为test.php
 *      则本文件使用return ['A' => APP_CORE . '/base/Test.php'];
 *
 *  友情提醒：
 *      命名空间的别名只是别名，在自动加载的时候会使用原名进行查找（即，无需在本文件添加别名的映射）
 */

return [
];