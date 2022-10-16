<?php
$path = './1.gif';
$content = str_repeat('1', 16 * 1024 * 1024);
var_dump(strlen($content));
file_put_contents($path, $content);
echo PHP_EOL;

echo 'fopen:', PHP_EOL;
$time = microtime(true);
$fp = fopen($path, 'r');
fseek($fp, 114514);
$content2 = fread($fp, 1024 * 1024);
fclose($fp);
var_dump(microtime(true) - $time);
var_dump(strlen($content2));
echo PHP_EOL;

echo 'file_get_contents:', PHP_EOL;
$time = microtime(true);
$content3 = file_get_contents($path);
$content3 = substr($content3, 114514, 1024 * 1024);
var_dump(microtime(true) - $time);
var_dump(strlen($content3));
echo PHP_EOL;

echo 'readfile:', PHP_EOL;
$time = microtime(true);
ob_start();
readfile($path);
$content4 = ob_get_clean();
$content4 = substr($content4, 114514, 1024 * 1024);
var_dump(microtime(true) - $time);
var_dump(strlen($content4));

unlink($path);