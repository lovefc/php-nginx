<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-23 17:42:53
 */

define("PATH", __DIR__);

require(PATH.'/require.php');

\FC\App::run();
// 在linux系统中 可以使用$_SERVER['_']来获取php执行文件的位置

echo \FC\Tools::colorFont("PHP-NGINX Starting....","绿").PHP_EOL;