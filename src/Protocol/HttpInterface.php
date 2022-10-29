<?php

namespace FC\Protocol;

use FC\HttpCode;

abstract class HttpInterface
{
    // 协议头
    public $protocolHeader= 'HTTP/1.1';

    public $separator = '\r\n';

    public $server;

    public $body = '';

    public $bodyLen = 0;

    public $headers;

    public $headerCode = 200;

    public $onMessage;

    public $onConnect;

    public $onClose;

    public $fd;

    public $getHeaders;

    public $types;

    public $cacheTime = 10;

    public $files = [];

    public $isHand = [];

    public $documentRoot = null; // 主目录

    public $defaultIndex = []; // 默认索引文件

    public $requestScheme = null; // http|https

    public $displayCatalogue = false;

    public $gzip = false;

    public $gzipTypes = [];

    public $gzipCompLevel = 0; // 压缩等级

    // 事件
    private $events = [
        'connect'=>'onConnect',
        'message'=>'onMessage',
        'close'=>'onClose'
    ];

    // 初始化参数
    public function init()
    {
        $this->httpCode = 200;
        $this->protocolHeader = 'HTTP/1.1';
        $this->separator = '\r\n';
        $this->headers = [
           'Content-Type'=>'text/html',
           'Connection'=>'keep-alive',
        ];
        $this->bodyLen = 0;
    }

    // 启动
    public function start()
    {
        $this->server->start();
    }

    // 设置系统参数
    public function setEnv($server_name)
    {
        $this->documentRoot = \FC\NginxConf::$Configs[$server_name]['root'][0] ?? null;
        $this->defaultIndex = \FC\NginxConf::$Configs[$server_name]['index'] ?? [];
        $this->displayCatalogue = \FC\NginxConf::$Configs[$server_name]['autoindex'][0] ?? 'off';
        $this->gzip = \FC\NginxConf::$Configs[$server_name]['gzip'][0] ?? 'off';
        $this->gzipCompLevel = \FC\NginxConf::$Configs[$server_name]['gzip_comp_level'][0] ?? 2;
        $this->gzipTypes = \FC\NginxConf::$Configs[$server_name]['gzip_types'] ?? [];
        //print_r($this->gzipTypes);
        //echo $this->gzipCompLevel.PHP_EOL;
        //echo $this->gzip.PHP_EOL;
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
        $_SERVER['REQUEST_SCHEME'] = $this->requestScheme;
        $_SERVER['HTTP_HOST'] = $server_name;
        $_SERVER['SERVER_NAME'] = $server_name;
        $_SERVER['SERVER_PORT'] = '';// 端口
        $_SERVER['SERVER_SOFTWARE'] = 'php-nginx/0.01';
        /*
            [REMOTE_PORT] => 65414
            [REMOTE_ADDR] => 127.0.0.1
            用户ip和端口
        */
    }

    // 连接
    public function _onConnect($server)
    {
        $client = $this->socketAccept($server);
        is_callable($this->onConnect) && call_user_func_array($this->onConnect, [$client]);
        return $client;
    }

    // 处理
    public function _onReceive($server, $fd, $data)
    {
        $this->fd = $fd;
        $this->init();
        if ($this->handleData($data)) {
            $this->setEnv($_SERVER['Host']);
            $query = IS_WIN ===TRUE ? iconv('UTF-8', 'GB2312', $_SERVER['QUERY']) : $_SERVER['QUERY'];
            $file = $this->getDefaultIndex($query);
            $status = $this->staticDir($file);
            if (!$status) {
                /*
                is_callable($this->onMessage) && call_user_func_array($this->onMessage, [$this, $data]);
                */
                $status = ($this->displayCatalogue=='on') ? $this->autoIndex($file) : false;
                if ($status==false) {
                    $this->page404();
                }
            }
        }
    }

    // 错误页面
    public function page404()
    {
        $data = '<html><head><title>404 Not Found</title></head><body><center><h1>404 Not Found</h1></center><hr><center>php-nginx/0.01</center></body></html>';
        $this->setHeader(404);
        $this->send($data);
    }

    // 目录索引
    public function autoIndex($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        // 这里是解决访问不带/的目录出错的问题
        if (substr($dir, -1)!='/') {
            $_SERVER['QUERY'] = $_SERVER['QUERY'].'/';
            $this->sendCode('302', ['Location'=>$_SERVER['QUERY']]);
            return true;
        }
        $handler = opendir($dir);
        $files = $dirs = [];
        while (($filename = readdir($handler)) !== false) {
            if ($filename !== "." && $filename !== "..") {
                $path = $dir.DIRECTORY_SEPARATOR.$filename;
                if (is_file($path)) {
                    $files[] = $filename;
                }
                if (is_dir($path)) {
                    $dirs[] = $filename."/";
                }
            }
        }
        closedir($handler);
        $html = '<html><head><title>Index of /</title></head><body><h1>Index of '.$_SERVER['QUERY'].'</h1><hr><pre><a href="../">../</a><br />';
        foreach ($dirs as  $value) {
            $html .= "<a href=\"./{$value}\">{$value}</a><br />";
        }
        foreach ($files as  $value) {
            $html .="<a href=\"./{$value}\">{$value}</a> <br />";
        }
        $html .= '</body></html>';
        $this->setHeader(200, ['Content-Type'=>'text/html']);
        $this->send($html);
        return true;
    }

    // 关闭
    public function _onClose($fd)
    {
        is_callable($this->onClose) && call_user_func_array($this->onClose, [$fd]);
    }


    // 解析获取的文件头
    public function _getHeader($code, $header = [])
    {
        $response = '';
        if (is_array($header) && count($header) > 0) {
            foreach ($header as $k=>$v) {
                if ($k=='Content-Type') {
                    $response .= "{$k}:{$v};charset=UTF-8".$this->separator;
                } else {
                    $response .= "{$k}:{$v}".$this->separator;
                }
            }
        }
        $response .= "Content-length:".$this->bodyLen.$this->separator;
        $response .= $this->separator;
        //echo $response.PHP_EOL;
        return $this->protocolHeader . " ". $this->getHttpCode($code) . $this->separator . $response;
    }

    // 设置文件头
    public function setHeader($code, $headers = '')
    {
        $this->headerCode = $code;
        if (!empty($headers) && is_array($headers)) {
            $this->headers = $headers;
        }
    }

    // 发送消息
    public function send($data, $bodylen=0)
    {
        $response = '';
        if (isset($this->headers['Content-Encoding'])  && $this->headers['Content-Encoding'] == 'gzip') {
            $data = \gzencode($data);
        }
        $len = strlen($data);
        if ($this->bodyLen == 0) {
            $this->bodyLen = ($bodylen!= 0) ? $bodylen : $len;
            $response =  $this->_getHeader($this->headerCode, $this->headers);
            $response = stripcslashes($response);
        }
        $response .= $data;
        $this->server->send($this->fd, $response);
        $response = '';
    }

    // 发送状态码
    public function sendCode($code, $headers=[])
    {
        $response =  $this->_getHeader($code, $headers);
        $response = stripcslashes($response);
        $this->server->send($this->fd, $response);
    }

    // 获取状态码
    public function getHttpCode($code)
    {
        return HttpCode::$STATUS_CODES[$code] ?? '';
    }

    // 获取http方法
    public function getHttpMethod($method)
    {
        return in_array($method, HttpCode::$METHODS[$method]) ?? false;
    }

    // 处理数据
    public function handleData($data)
    {
        //有文件头，来处理head头
        if (stripos($data, $this->protocolHeader)) {
            $data2 = explode("\r\n\r\n", $data)[0];
            $header = explode("\r\n", $data2);
            list($method, $query, $protocolHeader) = explode(" ", $header[0]);
            unset($header[0]);
            $head = [];
            // 这里，修复了时间戳的问题,不可只用:号来分割
            foreach ($header as $v) {
                $head2  = explode(":", $v);
                $v_num = strlen($head2[0].":");
                $v2 = substr($v, $v_num);
                $head[trim($head2[0])] = trim($v2);
            }
            $_SERVER = array_merge($_SERVER, $head);
            //$_SERVER['DOCUMENT_ROOT'] = $this->documentRoot ?? getcwd();
            $_SERVER['METHOD'] = $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['QUERY'] = $_SERVER['REQUEST_URI'] = urldecode($query);
            $_SERVER['QUERY_STRING'] = '';
            $head = $head2 = '';
            return true;
        }
        return false;
    }

    // 获取文件后缀
    public function getExt($filename)
    {
        $arr = pathinfo($filename);
        $ext = $arr['extension'];
        return strtolower($ext);
    }

    // 打开文件
    public function readTheFile($path)
    {
        $handle = fopen($path, "r");
        while (!feof($handle)) {
            yield fread($handle, 65535);
        }
        fclose($handle);
    }

    public function getDefaultIndex($query)
    {
        $arr =parse_url($query);
		//$arr2 = explode("/",$query);
		//print_r($arr2);
        $path =  $arr['path'] ?? '';
        $_SERVER['PHP_SELF'] = $path;
        $query2 = $arr['query'] ?? '';
        if ($path == '/') {
            foreach ($this->defaultIndex as $index) {
                $file = $this->documentRoot.DIRECTORY_SEPARATOR.$index;
                if (is_file($file)) {
                    $_SERVER['QUERY'] = $index."?{$query2}";
                    return $file;
                }
            }
        }
        return $this->documentRoot.$path;
    }

    // 静态目录绑定
    public function staticDir($file)
    {
        if (isset($this->files[$file]) || is_file($file)) {
            if (empty($this->types)) {
                $this->types = include(__DIR__.'/Type.php');
            }
            $type = $this->types;
            $ext = $this->getExt($file);
            $connect_type = $type[$ext] ?? null;
            /*
            if ($connect_type == 'application/x-httpd-php') {
                $php_path = \FC\App::getPhpPath();
                $command = $php_path.' -f '.$file;
                $data = \FC\App::realCmd($command);
                $this->send($data);
                return true;
            }
            */
            $lastTime = date('r');
            $is_cache = 0;
            if ($connect_type) {
                // 获取文件修改时间
                $fileTime = date('r', filemtime($file));
                $since = $_SERVER['If-Modified-Since'] ?? null;
                if ($since) {
                    $sinceTime = strtotime($since);
                    // 如果设置了缓存时间
                    if ($this->cacheTime!=0) {
                        //更新时间 大于等于 现在时间减去缓存时间
                        if ($sinceTime >= (time() - $this->cacheTime)) {
                            $is_cache = 1;
                        }
                    }

                    // 如果文件的最后时间小于当前时间
                    if ($sinceTime < time() && ($is_cache ==1)) {
                        $this->sendCode(304);
                        $is_cache = 1;
                    }
                }
                $headers = ['Content-Type'=>$connect_type,  'data'=>$lastTime];
                if ($this->gzip == 'on' && in_array($connect_type, $this->gzipTypes)) {
                    $headers['Content-Encoding']='gzip';//deflate';
                }
                $this->setHeader(200, $headers);
            } else {
                $headers = ["Content-Type"=>"application/octet-stream","Content-Transfer-Encoding"=>"Binary","Content-disposition"=>"attachment","filename"=>basename($file)];
                $this->setHeader(200, $headers);
            }
            if ($is_cache == 0) {
                //'Last-Modified'=>$lastTime,'Etag'=>md5($fileTime.$file), 'data'=>$lastTime, 'Cache-Control'=>'max-age='.$this->cacheTime
                if (isset($this->files[$file])) {
                    $filesize = $this->files[$file];
                } else {
                    $filesize = filesize($file);
                }
                if (isset($headers['Content-Encoding'])) {
                    // 常规的循环读取
                    foreach ($this->readTheFile($file) as $data) {
                        $this->send($data);
                    }
                } else {
                    // 常规的循环读取
                    foreach ($this->readTheFile($file) as $data) {
                        $this->send($data, $filesize);
                    }
                }

                $type = null;
                // 如果大于1000的文件，就重新搞
                if (count($this->files)>1000) {
                    $this->files = [];
                }
                return true;
            }
        }
        return false;
    }

    // 事件绑定
    public function on($event, $callback)
    {
        $event = $this->events[$event] ?? null;
        $this->$event = $callback;
    }
}
