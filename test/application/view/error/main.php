<?php
/**
 * Author: Alrash
 * Date: 2017/02/24 23:12
 * Description:
 */
use Aria\base\View;
?>
<head>
    <title>路由错误</title>
</head>
<body>
    <h1 style="text-align: center">路由错误</h1>
    <?php #View::renderNextByAlias('indexPage')?>
    <?php View::renderNext()?>
</body>
