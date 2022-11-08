<?php

/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-11-09 01:40:10
 */

use \FC\Code\{App,Tools};

require(__DIR__.'/src/require.php');

// 检查环境
Tools::checkEnvironment();

$arg = getopt('c:');

$confFile = isset($arg['c']) ? $arg['c'] : '';

$method = isset($argv[1]) ? strtolower(trim($argv[1])) : 0;

if ($method == '-v') {
    echo Tools::colorFont("[php-nginx] Version: ".VERSION, "绿").PHP_EOL;
    die();
}

if (!empty($confFile)) {
    $method = isset($argv[3]) ? strtolower(trim($argv[3])) : 0;
}

if ($method) {
    if ($method == 'start') {
        App::run($confFile);
        echo Tools::colorFont("PHP-NGINX Starting....", "绿").PHP_EOL;
    }
    if ($method == 'stop') {
        $text = App::stop($confFile);
        echo Tools::colorFont($text, "绿").PHP_EOL;
    }
    if ($method == 'restart') {
        App::run($confFile);
        echo \FC\Tools::colorFont("PHP-NGINX Restarting....", "绿").PHP_EOL;
    }
} else {
    echo Tools::colorFont("There are no operation parameters.", "红").PHP_EOL;
}
