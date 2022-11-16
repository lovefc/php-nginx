<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-11-10 10:25:54
 * @LastEditTime : 2022-11-10 12:35:01
 */

namespace FC\Code;

define('E_FATAL', E_ERROR | E_USER_ERROR |  E_CORE_ERROR | E_COMPILE_ERROR | E_RECOVERABLE_ERROR| E_PARSE);

trait ErrorHandler
{
    // 获取fatal error
    public function fatalHandler()
    {
        $error = error_get_last();
        if ($error && ($error["type"]===($error["type"] & E_FATAL))) {
            $errno   = $error["type"];
            $errfile = $error["file"];
            $errline = $error["line"];
            $errstr  = $error["message"];
            $this->errorHandler($errno, $errstr, $errfile, $errline);
        }
    }

    // 获取所有的error
    public function errorHandler($errno, $errstr, $errfile, $errline)
    {
        $log = date("Y/m/d H:i:s")." :".$errstr.$errfile.$errline.PHP_EOL;
        if (method_exists($this, 'errorLog')) {
            $this->errorLog($log);
        }
    }

    // 获取所有的异常,会中断执行,这里没有使用
    public function errorException($exception)
    {
        $log = date("Y/m/d H:i:s")." :".$exception->getMessage().PHP_EOL;
        if (method_exists($this, 'errorLog')) {
            $this->errorLog($log);
        }
    }
}
