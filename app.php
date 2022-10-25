<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-21 19:35:18
 */

define("PATH", __DIR__);

require(PATH.'/require.php');

function isFastCGI () {
    return !is_null($_SERVER['FCGI_SERVER_VERSION']);
}
var_dump(isFastCGI());
//\FC\NginxConf::readConf(PATH.'/conf/vhosts');
//echo \FC\App::config();

\FC\App::start();

