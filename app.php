<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-21 19:35:18
 */

ini_set("display_errors", "On");//打开错误提示

ini_set("error_reporting", E_ALL);//显示所有错误

ini_set('memory_limit', '128M');//定义使用内存

// 定义时区
!defined('TIMEZONE') ? date_default_timezone_set('PRC') : date_default_timezone_set(TIMEZONE);

// 定义编码
!defined('CHARSET') ? header("Content-type:text/html; charset=utf-8") : header('Content-type: text/html; charset=' . CHARSET);

// 定义版本
define("VERSION", "0.1");

define("PATH", __DIR__);

require(PATH.'/LoaderClass.php');

$prefix = "FC";

$base_dir = PATH."/src";

LoaderClass::AddPsr4($prefix, $base_dir);

// 自动加载
LoaderClass::register();


\FC\NginxConf::readConf(PATH.'/conf/vhosts');

function work($server_name)
{
	$port = \FC\NginxConf::$Configs[$server_name]['listen'][0] ?? 0;
	$cert = \FC\NginxConf::$Configs[$server_name]['ssl_certificate'][0] ?? null;
	$key  = \FC\NginxConf::$Configs[$server_name]['ssl_certificate_key'][0] ?? null;
    if (!empty($cert) && !empty($key)) {
        $context_option = array(
            'ssl' => array(
                'local_cert'  => $cert, // 也可以是crt文件
                'local_pk'    => $key,
                'verify_peer' => false, // 是否需要验证 SSL 证书,默认为true
            )
        );
        $obj = new \FC\Protocol\Https("0.0.0.0:{$port}", $context_option);
    } else {
        $obj = new \FC\Protocol\Http("0.0.0.0:{$port}");
    }
    $obj->on('connect', function ($fd) {
        echo "{$fd}已连接".PHP_EOL;
    });

    $obj->on('message', function ($server, $data) {
        $server->send(123);
    });

    $obj->on('close', function ($fd) {
        echo "{$fd}已关闭".PHP_EOL;
    });
    $obj->start();
}


$arg = getopt('c:');

$server_name = $arg['c'] ? $arg['c'] : null;

if(!$server_name) die('执行失败');

work($server_name);