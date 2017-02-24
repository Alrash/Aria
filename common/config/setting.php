<?php
/**
 * Author: Alrash
 * Date: 2017/01/16 22:17
 * Description: 通用配置文件（主要为宏定义）
 */

/*
 * 定义web项目目录路径
 *
 * 5.3.0新增__DIR__
 * __DIR__ 等价于 dirname(__FILE__)
 * */
defined('APP_ROOT') or define('APP_ROOT', dirname(dirname(__DIR__)));
//定义通用文件夹路径路径
defined('APP_COMMON_PATH') or define('APP_COMMON_PATH', APP_ROOT . '/common');
//定义核心项目文件夹路径
defined('APP_CORE_PATH') or define('APP_CORE_PATH', APP_ROOT . '/Aria');
//定义运行时文件路径，主要存放各种运行时信息，如日志信息
defined('APP_RUNTIME_PATH') or define('APP_RUNTIME_PATH', APP_ROOT . '/runtime');
//通用视图文件路径
defined('APP_COMMON_VIEW_PATH') or define('APP_COMMON_VIEW_PATH', APP_COMMON_PATH . '/view');
//log文件路径
defined('LOG_PATH') or define('LOG_PATH', APP_RUNTIME_PATH . '/logs');

//设置项目默认使用语言
defined('APP_LANG') or define('APP_LANG', 'zh-CN');

//定义是否启用调试，直接输出错误
defined('APP_DEBUG') or define('APP_DEBUG', true);

//id范围
const id_range = ['min_range' => 0, 'max_range' => PHP_INT_MAX];

//是否启用url美化，若项目单独使用，需注释本项，必须在所有项目的/config/setting.php文件中单独设置
#defined('URL_FORMAT') or define('URL_FORMAT', false);
