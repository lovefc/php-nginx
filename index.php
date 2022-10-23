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

//print_r(\FC\NginxConf::$Configs);


function work($server_name, $port, $cert='', $key='')
{
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

foreach (\FC\NginxConf::$Configs as $k=>$v) {
    $server_name = $k;
    $cert = $v['ssl_certificate'][0] ?? '';
    $key = $v['ssl_certificate_key'][0] ?? '';
    foreach ($v['listen'] as $port) {
        work($server_name, $port, $cert, $key);
    }
    //work($server_name, $port, $cert='', $key='');
}

/*
//apt-get install openssl
//openssl req -nodes -newkey rsa:2048 -keyout /home/wwwroot/php-static/conf/ssl/private/private.key -out  /home/wwwroot/php-static/conf/ssl/private/request.csr
//openssl x509 -req -days 1825 -in server.csr -signkey server.key -out server.crt

$context_option = array(
    'ssl' => array(
        'local_cert'  => __DIR__ . '/server.crt', // 也可以是crt文件
        'local_pk'    => __DIR__ . '/server.key',
        'verify_peer' => false, // 是否需要验证 SSL 证书,默认为true
    )
);

$obj2 = new \FC\Protocol\Https('0.0.0.0', $context_option);

$obj2->on('connect', function ($fd) {
   echo "{$fd}已连接".PHP_EOL;
});

$obj2->on('message', function ($server, $data) {
    $server->send(123);
});

$obj2->on('close', function ($fd) {
    echo "{$fd}已关闭".PHP_EOL;
});

$obj2->start();
*/