<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-25 11:03:22
 */

namespace FC;

class App
{
    //public static $is_win = (PATH_SEPARATOR == ';') ? true : false
    public static $phpPath;
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

    public static function execCmd($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows") {
            pclose(popen("start /B ".$cmd, "r"));
            sleep(1);
        } else {
            $cwd = $env = null;
            $process = proc_open($cmd, [], $pipes, $cwd, $env);
            if (is_resource($process)) {
                proc_close($process);
            }
        }
    }
	
	//调用命令获取输出
    public static function realCmd($command)
    {
        $handle = popen($command, 'r');
        $data = null;
        while (!feof($handle)) {
            $data .= fread($handle,1024);
        }
        pclose($handle);
        return $data;
    }

    public static function run()
    {
        \FC\NginxConf::readConf(PATH.'/conf/vhosts');
        //print_r(NginxConf::$Configs);
		$php_path = self::getPhpPath();
		//echo $php_path;
        foreach (\FC\NginxConf::$Configs as $k=>$v) {
            $server_name = $k;
            $cert = $v['ssl_certificate'][0] ?? '';
            $key = $v['ssl_certificate_key'][0] ?? '';
            foreach ($v['listen'] as $port) {
                self::$phpPath = $php_path;
                $cmd = $php_path.' '.PATH.'/app.php -h '.$server_name.' -p '.$port.' &';
                self::execCmd($cmd);
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
        //$document_root = NginxConf::$Configs[$server_name]['root'][0] ?? null;
        //$default_index = NginxConf::$Configs[$server_name]['index'] ?? [];
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
            $obj = new \FC\Protocol\Http("0.0.0.0:{$port}", []);
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
    public static function reStart()
    {
		self::stop();
		self::run();
		
    }
	
    public static function linuxStop()
    {
		$name = 'php.nginx';
	    $linux_cmd = "ps -ef | grep '$name' | grep -v 'grep' | awk '{print \$2}'";
		$output = shell_exec($linux_cmd);
		if(empty($output)) return 'Please start php-nginx first!';
		$arr = explode(PHP_EOL,$output);
		$s = array_filter($arr);
		foreach($arr as $pid){
		    shell_exec("kill -9 {$pid} 2>&1");
		}
		return 'PHP-NGINX Stoping....';
    }
	
	public static function winStop(){
		$win_cmd = 'taskkill /T /F /im php.exe';
		system($win_cmd);
		return 'PHP-NGINX Stoping....';
	}
	
    // 停止
    public static function stop()
    {
		if(IS_WIN == false){
			return self::linuxStop();
		}else{
			echo 111;
			return self::winStop();
		}
    }
	
}
