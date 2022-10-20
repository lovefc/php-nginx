<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-09-03 02:11:37
 */
 
ini_set("display_errors", "On");//打开错误提示

ini_set("error_reporting",E_ALL);//显示所有错误
 
ini_set('memory_limit','128M');

// 定义时区
!defined('TIMEZONE') ? date_default_timezone_set('PRC') : date_default_timezone_set(TIMEZONE);

// 定义编码
!defined('CHARSET') ? header("Content-type:text/html; charset=utf-8") : header('Content-type: text/html; charset=' . CHARSET);

define("PATH", __DIR__);

require(PATH.'/LoaderClass.php');

$prefix = "FC";

$base_dir = PATH."/src";

LoaderClass::AddPsr4($prefix, $base_dir);

// 自动加载
LoaderClass::register();


$context_option = array(
    'ssl' => array(
        'local_cert'  => __DIR__ . '/server.crt', // 也可以是crt文件
        'local_pk'    => __DIR__ . '/server.key',
        'verify_peer' => false, // 是否需要验证 SSL 证书,默认为true
    )
);



//$obj = new \FC\Http('0.0.0.0');
//, $context_option
$obj2 = new \FC\Protocol\Http('0.0.0.0:80');

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

//$obj->start();

/*
$server = new \FC\Worker('http://0.0.0.0');

$server->on('connect',function(){ });

$server->on('receive',function($server,$fd,$data){
        $data = '1111';
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/html;charset=UTF-8\r\n";
        $response .= "Connection: keep-alive\r\n";
        $response .= "Content-length: ".strlen($data)."\r\n\r\n";
        $response .= $data;
        $server->send($fd,$response);
});
$server->on('close',function($fd){
        echo 'onClose '.$fd.PHP_EOL;
});
$server->start();
*/
