<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-24 11:49:58
 */

namespace FC;

class App
{
    // 获取php文件位置
    public static function getPhpPath()
    {
        if (defined('PHP_BINARY') && PHP_BINARY && in_array(PHP_SAPI, array('cli', 'cli-server')) && is_file(PHP_BINARY)) {
            return PHP_BINARY;
        } elseif (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $paths = explode(PATH_SEPARATOR, getenv('PATH'));
            foreach ($paths as $path) {
                if (substr($path, strlen($path)-1) == DIRECTORY_SEPARATOR) {
                    $path = substr($path, 0, strlen($path)-1);
                }
                if (substr($path, strlen($path) - strlen('php')) == 'php') {
                    $response = $path.DIRECTORY_SEPARATOR . 'php.exe';
                    if (is_file($response)) {
                        return $response;
                    }
                } elseif (substr($path, strlen($path) - strlen('php.exe')) == 'php.exe') {
                    if (is_file($response)) {
                        return $response;
                    }
                }
            }
        } else {
            $paths = explode(PATH_SEPARATOR, getenv('PATH'));
            foreach ($paths as $path) {
                if (substr($path, strlen($path)-1) == DIRECTORY_SEPARATOR) {
                    $path = substr($path, strlen($path)-1);
                }
                if (substr($path, strlen($path) - strlen('php')) == 'php') {
                    if (is_file($path)) {
                        return $path;
                    }
                    $response = $path.DIRECTORY_SEPARATOR . 'php';
                    if (is_file($response)) {
                        return $response;
                    }
                }
            }
        }
        return null;
    }

    public static function config()
    {
        $php_ini = '';
        // php -info(-ini)也能获取到phpinfo的配置
        ob_start();
        phpinfo();
        $txt = ob_get_contents();
        ob_end_clean();
        if (preg_match("/Loaded\s+Configuration\s+File\s+=>\s+(.*)/i", $txt, $matches)) {
            $php_ini = $matches[1] ?? '';
        }
        return $php_ini;
    }

    public static function run()
    {
        \FC\NginxConf::readConf(PATH.'/conf/vhosts');
        foreach (\FC\NginxConf::$Configs as $k=>$v) {
            $server_name = $k;
            $cert = $v['ssl_certificate'][0] ?? '';
            $key = $v['ssl_certificate_key'][0] ?? '';
            foreach ($v['listen'] as $port) {
				$php_path = self::getPhpPath();
                $cmd = $php_path.' '.PATH.'/app.php -h '.$server_name.' -p '.$port.' &';
                $cwd = null;
                $env = null;
                //['bypass_shell'=>true,'blocking_pipes'=>true]
                $process = proc_open($cmd, [], $pipes, $cwd, $env);
                if (is_resource($process)) {
                    // 切记：在调用 proc_close 之前关闭所有的管道以避免死锁。
                    proc_close($process);
                }
            }
        }
    }
    public static function work($server_name, $port)
    {
		\FC\NginxConf::readConf(PATH.'/conf/vhosts');
        $process_title = "php.nginx-{$server_name}";//PHP 5.5.0
        cli_set_process_title($process_title);
        $cert = NginxConf::$Configs[$server_name]['ssl_certificate'][0] ?? null;
        $key  = NginxConf::$Configs[$server_name]['ssl_certificate_key'][0] ?? null;
	    $document_root = NginxConf::$Configs[$server_name]['root'][0] ?? null;
		$default_index = NginxConf::$Configs[$server_name]['index'] ?? [];
        if (!empty($cert) && !empty($key)) {
            $context_option = array(
                'ssl' => array(
                    'local_cert'  => $cert, // 也可以是crt文件
                    'local_pk'    => $key,
                    'verify_peer' => false, // 是否需要验证 SSL 证书,默认为true
                )
            );
            $obj = new \FC\Protocol\Https("0.0.0.0:{$port}", $context_option, $document_root, $default_index);
        } else {
            $obj = new \FC\Protocol\Http("0.0.0.0:{$port}",[], $document_root, $default_index);
        }
		/*
        $obj->on('connect', function ($fd) {
        });
        $obj->on('message', function ($server, $data) {
            $server->send('Welcome php-nginx');
        });
        $obj->on('close', function ($fd) {
        });
		*/
        $obj->start();
    }

    public static function getArgs($key)
    {
        $args = getopt("{$key}:");
        $arg = isset($args[$key]) ? $args[$key] : null;
        return $arg;
    }

        // 启动
    public static function start()
    {
        $arg = getopt('h:p:');
        $server_name = isset($arg['h']) ? $arg['h'] : '127.0.0.1';
        $port = isset($arg['p']) ? $arg['p'] : '80';
        (!$server_name || !$port) && die('执行失败');
        self::work($server_name, $port);
    }

        // 重启
    public static function restart()
    {
    }

        // 停止
    public static function stop()
    {
    }
}
