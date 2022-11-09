<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-11-09 01:38:04
 */
namespace FC\Protocol;

use FC\Code\{HttpCode,NginxConf, Client as fpmClient};

abstract class HttpInterface
{
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

    public $clientHeads = [];

    public $types;

    public $cacheTime = 0;

    public $files = [];

    public $isHand = [];

    public $outputStatus = false;

    public $documentRoot = null; // 主目录

    public $defaultIndex = []; // 默认索引文件

    public $requestScheme = null; // http|https

    public $displayCatalogue = false;

    public $gzip = false;

    public $gzipTypes = [];

    public $gzipCompLevel = 0;

    public $addHeaders = [];

    public $errorPage = '';

    public $locations;

    public $accessLogFile = '';

    public $errorLogFile = '';

    public $setenvStatus = 0;

    public $remoteAddress;

    public $clientBody = '';

    private $fpmClient;

    private $firstRead = false; // 首次读取

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
    }

    // 启动
    public function start()
    {
        $this->server->start();
    }

    public function getHost($text)
    {
        $tmp = \explode(':', $text);
        $_SERVER['SERVER_ADDR'] = $tmp[0] ?? '';// 地址
        $_SERVER['SERVER_PORT'] = $tmp[1] ?? '';// 端口
    }

    // 设置系统参数
    public function setEnv($server_name)
    {
        if ($this->setenvStatus ==0) {
            $this->documentRoot = NginxConf::$Configs[$server_name]['root'][0] ?? null;
            $this->defaultIndex = NginxConf::$Configs[$server_name]['index'] ?? [];
            $this->displayCatalogue = NginxConf::$Configs[$server_name]['autoindex'][0] ?? 'off';
            $this->gzip = NginxConf::$Configs[$server_name]['gzip'][0] ?? 'off';
            $this->gzipCompLevel = NginxConf::$Configs[$server_name]['gzip_comp_level'][0] ?? 2;
            $this->gzipTypes = NginxConf::$Configs[$server_name]['gzip_types'] ?? [];
            $this->addHeaders = NginxConf::$Configs[$server_name]['add_header'] ?? [];
            $this->errorPage = NginxConf::$Configs[$server_name]['error_page'][0] ?? '';
            $this->locations = NginxConf::$Configs[$server_name]['location'] ?? '';
            $this->accessLogFile = NginxConf::$Configs[$server_name]['access_log'][0] ?? '';
            $this->errorLogFile = NginxConf::$Configs[$server_name]['error_log'][0] ?? '';
            $_SERVER['DOCUMENT_ROOT'] = $_SERVER['PATH_TRANSLATED'] =  $this->documentRoot;
            $_SERVER['SERVER_SOFTWARE'] = 'php-nginx/0.01';
            $this->setenvStatus = 1;
            $_SERVER['REQUEST_SCHEME'] = $this->requestScheme;
            $_SERVER['HTTP_HOST'] = $server_name;
            $_SERVER['SERVER_NAME'] = $server_name;
            $_SERVER['SERVER_PROTOCOL'] = $this->protocolHeader;
            $_SERVER['GATEWAY_INTERFACE'] = 'CGI/1.1';
        }
        $address = explode(":", $this->remoteAddress);
        $_SERVER['REMOTE_ADDR'] = $address[0] ?? '';
        $_SERVER['REMOTE_PORT'] = $address[1] ?? '';
        $this->explodeQuery();
    }

    // 解析query
    public function explodeQuery()
    {
        $tmp = explode("?", $_SERVER['QUERY']);
        $_SERVER['SCRIPT_NAME'] = $_SERVER['DOCUMENT_URI'] =  $_SERVER['PHP_SELF'] = $tmp[0] ?? '';
        $_SERVER['QUERY_STRING'] = $tmp[1] ?? '';
        $_SERVER['SCRIPT_FILENAME'] = $this->documentRoot.$_SERVER['PHP_SELF'];
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
        $this->outputStatus = false;
        // 删除文件判断缓存
        clearstatcache();
        if ($this->handleData($data)) {
            $tmp = explode(":", $_SERVER['Host'])[0];
            $this->setEnv($tmp);
            $query = IS_WIN ===true ? iconv('UTF-8', 'GB2312', $_SERVER['QUERY']) : $_SERVER['QUERY'];
            $file = $this->getDefaultIndex($query);
            $this->explodeQuery();
            !$this->outputStatus && $this->analysisLocation($_SERVER['PHP_SELF']);
            !$this->outputStatus && $this->staticDir($file);
            !$this->outputStatus && $this->displayCatalogue=='on' && $this->autoIndex($file);
            !$this->outputStatus && $this->errorPageShow(404);
            $this->accessLog();
        }
    }

    // 错误页面
    public function errorPageShow($code)
    {
        if ($this->errorPage) {
            $this->staticDir($this->errorPage);
        } else {
            $text = $this->getHttpCodeValue($code);
            $data = '<html><head><title>'.$text.'</title></head><body><center><h1>'.$text.'</h1></center><hr><center>php-nginx/0.01</center></body></html>';
            $this->setHeader($code);
            $this->send($data);
        }
        $this->outputStatus = true;
    }

    // 链接fpm
    public function fastcgiPHP($host = '127.0.0.1', $port = '9000')
    {
        $client = new fpmClient($host, $port);
        $content = $this->clientBody;
        $server = [
            'GATEWAY_INTERFACE' => 'FastCGI/1.0',
            'SERVER_SOFTWARE' => 'php/fcgiclient',
            'SERVER_PROTOCOL' => 'HTTP/1.1',
            'CONTENT_TYPE' => $_SERVER['Content-Type'] ?? '',
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'],
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'],
            'REMOTE_PORT' => $_SERVER['REMOTE_PORT'],
            'SERVER_ADDR' => $_SERVER['SERVER_ADDR'],
            'SERVER_PORT' => $_SERVER['SERVER_PORT'],
            'SERVER_NAME' => $_SERVER['SERVER_NAME'],
            'CONTENT_LENGTH' => $_SERVER['Content-Length'] ?? '',
            'QUERY_STRING' => $_SERVER['QUERY_STRING'],
            'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'],
            'DOCUMENT_URI' => $_SERVER['DOCUMENT_URI'],
            'PHP_SELF' => $_SERVER['PHP_SELF'],
            'HTTP_CONTENT_TYPE'=>$_SERVER['Content-Type'] ?? '',
            'HTTP_CONTENT_LENGTH'=>$_SERVER['Content-Length'] ?? '',
            'HTTP_HOST' => $_SERVER['SERVER_NAME'] ?? '',
            'HTTP_ACCEPT_LANGUAGE' => $_SERVER['Accept-Language'] ?? '',
            'HTTP_ACCEPT_ENCODING' => $_SERVER['Accept-Encoding'] ?? '',
            'HTTP_SEC_FETCH_DEST' => $_SERVER['Sec-Fetch-Dest'] ?? '',
            'HTTP_SEC_FETCH_USER' => $_SERVER['Sec-Fetch-User'] ?? '',
            'HTTP_SEC_FETCH_MODE' => $_SERVER['Sec-Fetch-Mode'] ?? '',
            'HTTP_SEC_FETCH_SITE' => $_SERVER['Sec-Fetch-Site'] ?? '',
            'HTTP_ACCEPT' => $_SERVER['Accept'] ?? '',
            'HTTP_USER_AGENT' => $_SERVER['User-Agent'] ?? '',
        ];
        $server = array_merge($this->clientHeads, $server);
        $client->setConnectTimeout(100);
        $client->setReadWriteTimeout(500);
        //$client->setKeepAlive(true);
        $text = $client->request($server, $content);
        if (empty($text)) {
            $this->errorPageShow(502);
            $this->outputStatus = true;
            return;
        }
        $arr = explode("\r\n\r\n", $text);
        $header_text = $arr[0] ?? [];
        $content = $arr[1] ?? '';
        if (trim($content) == 'File not found.') {
            $this->errorPageShow(404);
            $this->outputStatus = true;
            return;
        }
        $headers = explode("\n", $header_text);
        foreach ($headers as $v) {
            $head2  = explode(":", $v);
            $v_num = strlen($head2[0].":");
            $v2 = substr($v, $v_num);
            $this->headers[trim($head2[0])] = trim($v2);
        }
        /** 这里要获取到fpm里面设置的状态码和header头 **/
        $code = isset($this->headers['Status']) ? $this->getHttpCode($this->headers['Status']) : 200;
        $this->setHeader($code, $this->headers);
        $this->send($content);
        $server = [];
        $this->clientBody = $client = '';
        $this->outputStatus = true;
    }

    // 换算时间
    public function timeConversion($text)
    {
        if (empty($text)) {
            return 0;
        }
        $str = strtolower(substr($text, -1));
        $time = substr($text, 0, strlen($text)-1);
        switch($str) {
            case 's':
                $time = $time;
                break;
            case 'm':
                $time = 60*$time;
                break;
            case 'h':
                $time = 3600*$time;
                break;
            case 'd':
                $time = 86400*$time;
                break;
            default:
                $time =  $text;
        }
        return $time;
    }

    // 缓存文件
    public function cacheFile($cacheTime)
    {
        $since = $_SERVER['If-Modified-Since'] ?? null;
        $time = time();
        if ($since) {
            $sinceTime = strtotime($since);
            if (($sinceTime + $cacheTime) >= $time) {
                $this->sendCode(304, ['Last-Modified'=>date('r'), 'Cache-Control'=>'max-age='.$cacheTime]);
                $this->outputStatus = true;
                return true;
            }
        }
        return false;
    }

    // 解析参数
    public function analysisLocationValue($text)
    {
        $text = str_replace(";", '', $text);
        $arrs = array_values(array_filter(explode(" ", trim($text))));
        $key = $arrs[0] ?? '';
        $value = $arrs[1] ?? '';
        // 缓存
        if (strtolower($key) == 'expires') {
            $time = $this->timeConversion($value);
            if ($this->cacheFile($time)) {
                return true;
            }
            $lastTime = date('r');
            //'Expires'=> date('r',time() + 7200), 'Age'=>7200,'Accept-Ranges'=>'bytes','Etag'=>md5($_SERVER['SCRIPT_FILENAME']),
            $heads = ['Last-Modified'=>$lastTime, 'Cache-Control'=>'max-age='.$time];
            $this->addHeaders = array_merge($this->addHeaders, $heads);
        }

        // 解析php
        if (strtolower($key) == 'fastcgi_pass') {
            if (!is_file($_SERVER['SCRIPT_FILENAME'])) {
                return false;
            }
            $tmp = explode(":", $value);
            $host = $tmp[0] ?? false;
            $port = $tmp[1] ?? false;
            if (!$host || !$port) {
                return false;
            }
            $this->fastcgiPHP($host, $port);
        }
    }

    // 解析参数
    public function analysisLocation($query)
    {
        if (!empty($this->locations)) {
            foreach ($this->locations as $k=>$v) {
                if (preg_match("/{$k}/i", $query, $matches)) {
                    $this->analysisLocationValue($v);
                }
            }
        }
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
        $this->outputStatus = true;
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
        return $this->protocolHeader . " ". $this->getHttpCodeValue($code) . $this->separator . $response;
    }

    // 发送状态码
    public function sendCode($code, $headers=[])
    {
        $this->setHeader($code, $headers);
        $response =  $this->_getHeader($code, $headers);
        $response = stripcslashes($response);
        $this->server->send($this->fd, $response);
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
        $str_len =  strlen($data); // 当前字符串大小
        $bodylen = ($bodylen==0) ? $str_len : $bodylen;	// 判断有没有传入大小
        // 说明只有一片
        if ($str_len == $bodylen) {
            $this->body = $data;
            $this->bodyLen = $str_len;
        } else {
            $len = $this->bodyLen+$str_len;
            $this->body .= $data;
            $this->bodyLen += $str_len;
            if ($len < $bodylen) {
                return false;
            }
        }
        // gzip压缩
        if (isset($this->headers['Content-Encoding'])  && $this->headers['Content-Encoding'] == 'gzip') {
            $this->body = \gzencode($this->body);
            $this->bodyLen = strlen($this->body);
        }
        $this->headers = array_merge($this->headers, $this->addHeaders);
        $response =  $this->_getHeader($this->headerCode, $this->headers);
        $response = stripcslashes($response);
        $response .= $this->body;
        $this->server->send($this->fd, $response);
        $this->body = '';
        $this->bodyLen	= 0;
        $this->clientHeads = [];
        $this->clientBody = '';
    }

    // 访问日志
    public function accessLog()
    {
        $log = $_SERVER['REMOTE_ADDR']." - - ".date("Y-m-d H:i:s")." ".$_SERVER['REQUEST_METHOD']." ".$_SERVER['QUERY']." ".$_SERVER['SERVER_PROTOCOL']." \"".$_SERVER['User-Agent']."\"".PHP_EOL;
        echo $log;
        if (!empty($this->accessLogFile) && is_dir(dirname($this->accessLogFile))) {
            file_put_contents($this->accessLogFile, $log, FILE_APPEND);
        }
    }

    // 错误日志
    public function errorLog()
    {
        //2022/10/19 11:05:53 [warn] 34572#24684: conflicting server name "cs.com" on 0.0.0.0:80, ignored
    }

    // 获取状态码-根据code找值
    public function getHttpCodeValue($code)
    {
        return HttpCode::$STATUS_CODES[$code] ?? '';
    }

    // 获取状态码-根据值找键名
    public function getHttpCode($value)
    {
        return array_search($value, HttpCode::$STATUS_CODES);
    }

    // 处理数据
    public function handleData($data)
    {
        //有文件头，来处理head头
        //if (stripos($data, $this->protocolHeader)) {
        if (!$this->firstRead && stripos($data, $this->protocolHeader)) {
            $buffer = explode("\r\n\r\n", $data);
            $data2 = $buffer[0] ?? '';
            unset($buffer[0]);
            $this->clientBody = implode("\r\n\r\n", $buffer);
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
                $this->clientHeads[trim($head2[0])] = trim($v2);
            }
            //如果没有这个值
            if (!isset($head['If-Modified-Since']) && isset($_SERVER['If-Modified-Since'])) {
                unset($_SERVER['If-Modified-Since']);
            }
            $_SERVER = array_merge($_SERVER, $head);
            $_SERVER['METHOD'] = $_SERVER['REQUEST_METHOD'] = $method;
            $_SERVER['QUERY'] = $_SERVER['REQUEST_URI'] = urldecode($query);
            $head = $head2 = $buffer = '';
            $this->firstRead = 1;
        } else {
            $this->clientBody .= $data;
        }

        if (!isset($_SERVER['Content-Length'])) {
            $this->firstRead = 0;
            return true;
        }
        if (strlen($this->clientBody) == $_SERVER['Content-Length']) {
            $this->firstRead = 0;
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

    // 获取索引文件路径
    public function getDefaultIndex($query)
    {
        $arr =parse_url($query);
        $path =  $arr['path'] ?? '';
        $query2 = isset($arr['query']) ? "?".$arr['query'] : '';
        if (substr($path, -1) == '/') {
            foreach ($this->defaultIndex as $index) {
                $file = $this->documentRoot.$path.$index;
                if (is_file($file)) {
                    $_SERVER['QUERY'] = $path.$index."{$query2}";
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
            $lastTime = date('r');
            $time = time();
            $is_cache = 0;
            if ($connect_type) {
                $headers = ['Content-Type'=>$connect_type,  'Accept-Ranges'=>'bytes', 'Data'=>$lastTime];
                if ($this->gzip == 'on' && in_array($connect_type, $this->gzipTypes)) {
                    $headers['Content-Encoding']='gzip';//deflate';
                }

                $this->setHeader(200, $headers);
            } else {
                $headers = ["Content-Type"=>"application/octet-stream","Content-Transfer-Encoding"=>"Binary", "Content-disposition"=>"attachment","filename"=>basename($file)];
                $this->setHeader(200, $headers);
            }
            if ($is_cache == 0) {
                if (isset($this->files[$file])) {
                    $filesize = $this->files[$file];
                } else {
                    $filesize = filesize($file);
                }
                // 常规的循环读取
                foreach ($this->readTheFile($file) as $data) {
                    $this->send($data, $filesize);
                }
                $type = null;
                // 如果大于1000的文件，就重新搞
                if (count($this->files)>1000) {
                    $this->files = [];
                }
                $this->outputStatus = true;
            }
        }
    }

    // 事件绑定
    public function on($event, $callback)
    {
        $event = $this->events[$event] ?? null;
        $this->$event = $callback;
    }
}
