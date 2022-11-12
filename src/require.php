<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-23 17:42:53
 */


ini_set("display_errors", "Off");//关闭错误提示

ini_set("error_reporting", 0);//E_ALL显示所有错误

ini_set('memory_limit', '512M');//定义使用内存

// 定义版本
define("VERSION", "0.01");

// 定义目录
define("PATH", dirname(__DIR__));

// 定义时区
!defined('TIMEZONE') ? date_default_timezone_set('PRC') : date_default_timezone_set(TIMEZONE);

// 定义编码
!defined('CHARSET') ? header("Content-type:text/html; charset=utf-8") : header('Content-type: text/html; charset=' . CHARSET);

// 检查是否为win系统
$is_win = PATH_SEPARATOR == ';' ? true : false;

define('IS_WIN',$is_win);

require(__DIR__.'/Code/LoaderClass.php');

$prefix = "FC";

$base_dir = __DIR__;

\FC\Code\LoaderClass::AddPsr4($prefix, $base_dir);

// 自动加载
\FC\Code\LoaderClass::register();
 
 