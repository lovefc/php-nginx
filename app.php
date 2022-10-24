<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-21 19:35:18
 */

define("PATH", __DIR__);

require(PATH.'/require.php');

//\FC\NginxConf::readConf(PATH.'/conf/vhosts');

\FC\App::start();