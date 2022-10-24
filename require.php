<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-23 17:42:53
 */

ini_set("display_errors", "On");//打开错误提示

ini_set("error_reporting", E_ALL);//显示所有错误

ini_set('memory_limit', '128M');//定义使用内存

// 定义时区
!defined('TIMEZONE') ? date_default_timezone_set('PRC') : date_default_timezone_set(TIMEZONE);

// 定义编码
!defined('CHARSET') ? header("Content-type:text/html; charset=utf-8") : header('Content-type: text/html; charset=' . CHARSET);

// 定义版本
define("VERSION", "0.01");

// 检查是否为win系统
$is_win = PATH_SEPARATOR == ';' ? true : false;

define('IS_WIN',$is_win);

require(PATH.'/LoaderClass.php');

$prefix = "FC";

$base_dir = PATH."/src";

LoaderClass::AddPsr4($prefix, $base_dir);

// 自动加载
LoaderClass::register();