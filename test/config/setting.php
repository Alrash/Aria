<?php
/**
 * Author: Alrash
 * Date: 2017/01/16 22:13
 * Description: 静态配置文件
 */

/*
 * **********************************************************************************************************
 * */
//项目环境名
defined('APP_ENV') or define('APP_ENV', preg_replace('/.*\//', '', dirname(__DIR__)));
//项目根路径
defined('APP_ENV_PATH') or define('APP_ENV_PATH', APP_ROOT . '/' . APP_ENV);

//项目控制器路径
defined('APP_APPLICATION_PATH') or define('APP_APPLICATION_PATH', APP_ROOT . '/' . APP_ENV . '/application');
//项目视图路径
defined('APP_VIEW_PATH') or define('APP_VIEW_PATH', APP_APPLICATION_PATH . '/view');
/*
 * 以上无需更改
 * **********************************************************************************************************
 * */
