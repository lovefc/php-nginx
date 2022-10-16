<?php

declare(strict_types=1);

$path = __DIR__ . '/test.tmp';
$content = str_repeat('1', 16 * 1024 * 1024);
var_dump(\strlen($content));
file_put_contents($path, $content);
echo \PHP_EOL;

echo 'fopen:', \PHP_EOL;
$time = microtime(true);
$size = 0;
$fp = fopen($path, 'r');
while (!feof($fp))
{
    $tmp = fread($fp, 4096);
    $size += \strlen($tmp);
}
fclose($fp);
var_dump($size);
var_dump(microtime(true) - $time);
echo \PHP_EOL;

echo 'file_get_contents:', \PHP_EOL;
$time = microtime(true);
$content3 = file_get_contents($path);
var_dump(\strlen($content3));
var_dump(microtime(true) - $time);
echo \PHP_EOL;

echo 'readfile:', \PHP_EOL;
$time = microtime(true);
ob_start();
readfile($path);
$content4 = ob_get_clean();
var_dump(\strlen($content4));
var_dump(microtime(true) - $time);

unlink($path);