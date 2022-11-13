<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-11-13 01:11:00
 */

namespace FC\Protocol;

use FC\Code\{HttpCode, NginxConf, Tools, ErrorHandler, Client as fpmClient};

class HttpInterface
{
    use ErrorHandler;

    public $protocolHeader = 'HTTP/1.1';

    public $separator = '\r\n';

    public $server;

    public $body = '';

    public $bodyLen = 0;

    public $rangeSize = 1000 * 1000 * 1;

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

    public $outputStatus = false;

    public $documentRoot = null;

    public $defaultIndex = [];

    public $requestScheme = null;

    public $displayCatalogue = false;

    public $gzip = false;

    public $gzipTypes = [];

    public $gzipCompLevel = 0;

    public $addHeaders = [];

    public $errorPage;

    public $locations;

    public $accessLogFile = '';

    public $errorLogFile = '';

    public $remoteAddress;

    public $clientBody = '';

    private $fpmClient;

    private $firstRead = false; // 首次读取

    private $downTypes = [
        'application/zip',
        'application/msword',
        'application/octet-stream',
    ];

    // 事件
    private $events = [
        'connect' => 'onConnect',
        'message' => 'onMessage',
        'close' => 'onClose'
    ];

    public function socketAccept($server)
    {
    }

    // 初始化参数
    public function init()
    {
        $this->httpCode = 200;
        $this->protocolHeader = 'HTTP/1.1';
        $this->separator = '\r\n';
        $this->headers = [
            'Content-Type' => 'text/html',
            'Connection' => 'keep-alive',
        ];
    }

    // 启动
    public function start()
    {
        $this->server->start();
    }

    // 获取端口号
    public function getHost($text)
    {
        $tmp = \explode(':', $text);
        $_SERVER['SERVER_ADDR'] = $tmp[0] ?? ''; // 地址
        $_SERVER['SERVER_PORT'] = $tmp[1] ?? ''; // 端口
    }

    // 设置系统参数
    public function setEnv($server_name)
    {
        $this->documentRoot = NginxConf::$Configs[$server_name]['root'][0] ?? null;
        $this->defaultIndex = NginxConf::$Configs[$server_name]['index'] ?? [];
        $this->displayCatalogue = NginxConf::$Configs[$server_name]['autoindex'][0] ?? 'off';
        $this->gzip = NginxConf::$Configs[$server_name]['gzip'][0] ?? 'off';
        $this->gzipCompLevel = NginxConf::$Configs[$server_name]['gzip_comp_level'][0] ?? 2;
        $this->gzipTypes = NginxConf::$Configs[$server_name]['gzip_types'] ?? [];
        $this->addHeaders = NginxConf::$Configs[$server_name]['add_header'] ?? [];
        $this->errorPage = NginxConf::$Configs[$server_name]['error_page'] ?? '';
        $this->locations = NginxConf::$Configs[$server_name]['location'] ?? '';
        $this->accessLogFile = NginxConf::$Configs[$server_name]['access_log'][0] ?? '';
        $this->errorLogFile = NginxConf::$Configs[$server_name]['error_log'][0] ?? '';
        $_SERVER['DOCUMENT_ROOT'] = $_SERVER['PATH_TRANSLATED'] =  $this->documentRoot;
        $_SERVER['SERVER_SOFTWARE'] = 'php-nginx/0.01';
        $_SERVER['REQUEST_SCHEME'] = $this->requestScheme;
        $_SERVER['HTTP_HOST'] = $server_name;
        $_SERVER['SERVER_NAME'] = $server_name;
        $_SERVER['SERVER_PROTOCOL'] = $this->protocolHeader;
        $_SERVER['GATEWAY_INTERFACE'] = 'CGI/1.1';
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
        $_SERVER['SCRIPT_FILENAME'] = $this->documentRoot . $_SERVER['PHP_SELF'];
    }

    // 连接
    public function _onConnect($server)
    {
        if (method_exists($this, 'socketAccept')) {
            $client = $this->socketAccept($server);
        }
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
            $query = IS_WIN === true ? iconv('UTF-8', 'GB2312', $_SERVER['QUERY']) : $_SERVER['QUERY'];
            $file = $this->getDefaultIndex($query);
            $this->explodeQuery();
            !$this->outputStatus && $this->analysisLocation($_SERVER['PHP_SELF']);
            !$this->outputStatus && $this->staticDir($file);
            !$this->outputStatus && $this->displayCatalogue == 'on' && $this->autoIndex($file);
            !$this->outputStatus && $this->errorPageShow(404);
            $this->accessLog();
        }
    }

    // 错误页面
    public function errorPageShow($code)
    {
        if ($this->errorPage) {
            foreach ($this->errorPage as $k => $v) {
                if ($k == $code) {
                    if (Tools::checkUrl($v)) {
                        $this->sendCode('302', ['Location' => $v]);
                    } else {
                        $this->staticDir($v);
                    }
                }
            }
        } else {
            $text = $this->getHttpCodeValue($code);
            $data = '<html><head><title>' . $text . '</title></head><body><center><h1>' . $text . '</h1></center><hr><center>php-nginx/0.01</center></body></html>';
            $this->setHeader($code);
            $this->send($data);
        }
        $this->outputStatus = true;
    }

    // 连接fpm
    public function fastcgiPHP($host = '127.0.0.1', $port = '9000')
    {
        if (Tools::checkIp($host) === false) {
            $host = 'unix://' . $host;
            $port = '-1';
        }
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
            'HTTP_CONTENT_TYPE' => $_SERVER['Content-Type'] ?? '',
            'HTTP_CONTENT_LENGTH' => $_SERVER['Content-Length'] ?? '',
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
        $client->setReadWriteTimeout(1000);
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
        if (strstr($header_text, "PHP message:")) {
            $tmp = explode("\n", $header_text);
            $tmp2 = preg_split("/(Status:|Content-Type:)+/", $tmp[0]);
            $content = $tmp2[0];
        }
        if (trim($content) == 'File not found.') {
            $this->errorPageShow(404);
            $this->outputStatus = true;
            return;
        }
        $headers = explode("\n", $header_text);
        $_headers = [];
        foreach ($headers as $v) {
            $head2  = explode(":", $v);
            $v_num = strlen($head2[0] . ":");
            $v2 = substr($v, $v_num);
            $_headers[trim($head2[0])] = trim($v2);
        }
        /** 这里要获取到fpm里面设置的状态码和header头 **/
        $code = isset($_headers['Status']) ? $this->getHttpCode($_headers['Status']) : 200;
        if (isset($_headers['Content-Length'])) {
            $_headers['Content-Length'] = strlen($content);
        }
        $this->setHeader($code, $_headers);
        $this->send($content);
        $server = [];
        $this->clientBody = $client = '';
        $this->outputStatus = true;
    }

    // 缓存文件
    public function cacheFile($cacheTime)
    {
        $since = $_SERVER['If-Modified-Since'] ?? null;
        $time = time();
        if ($since) {
            $sinceTime = strtotime($since);
            if (($sinceTime + $cacheTime) >= $time) {
                $this->sendCode(304, ['Last-Modified' => date('r'), 'Cache-Control' => 'max-age=' . $cacheTime]);
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
        $arr = array_values(array_filter(explode(" ", trim($text))));
        $key = $arr[0] ?? '';
        $value = $arr[1] ?? '';
        if (strtolower($key) == 'expires') {
            $time = Tools::timeConversion($value);
            if ($this->cacheFile($time)) {
                return true;
            }
            $lastTime = date('r');
            $heads = ['Last-Modified' => $lastTime, 'Cache-Control' => 'max-age=' . $time];
            $this->addHeaders = array_merge($this->addHeaders, $heads);
        }
        if (strtolower($key) == 'return') {
            if (is_numeric($value)) {
                $this->errorPageShow($value);
                $this->outputStatus = true;
                return;
            }
            if (Tools::checkUrl($value)) {
                $this->sendCode('302', ['Location' => $value]);
                $this->outputStatus = true;
                return;
            }
        }
        if (strtolower($key) == 'fastcgi_pass') {
            if (!is_file($_SERVER['SCRIPT_FILENAME'])) {
                return false;
            }
            $tmp = explode(":", $value);
            $host = $tmp[0] ?? false;
            $port = $tmp[1] ?? false;
            if (!$host) {
                return false;
            }
            $this->fastcgiPHP($host, $port);
        }
    }

    // 解析参数
    public function analysisLocation($query)
    {
        if (!empty($this->locations)) {
            foreach ($this->locations as $k => $v) {
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
        if (substr($dir, -1) != '/') {
            $_SERVER['QUERY'] = $_SERVER['QUERY'] . '/';
            $this->sendCode('302', ['Location' => $_SERVER['QUERY']]);
            return true;
        }
        $handler = opendir($dir);
        $files = $dirs = [];
        $i = 0;
        $max_len = 0;
        while (($filename = readdir($handler)) !== false) {
            if ($filename !== "." && $filename !== "..") {
                $path = $dir . DIRECTORY_SEPARATOR . $filename;
                if (is_file($path)) {
                    $files[$i]['filename'] = $filename;
                    $files[$i]['uptime'] = filemtime($path);
                    $filename = iconv('utf-8', 'gb2312', $filename);
                    $len =  strlen($filename);
                    $files[$i]['filesize'] = Tools::transfByte(filesize($path));
                    if ($max_len < $len) {
                        $max_len = $len;
                    }
                }
                if (is_dir($path)) {
                    $dirs[] = $filename . "/";
                }
                $i++;
            }
        }
        $max_len += 15;
        closedir($handler);
        $html = '<html><head><title>Index of /</title></head><body><h1>Index of ' . $_SERVER['PHP_SELF'] . '</h1><hr><pre><a href="../">../</a>' . PHP_EOL;
        foreach ($dirs as  $d) {
            $html .= "<a href=\"./{$d}\">{$d}</a>" . Tools::spaces($d, $max_len) . " -" . PHP_EOL;
        }
        foreach ($files as  $k => $f) {
            $name = $f['filename'];
            $uptime = date("Y-m-d H:i:s", $f['uptime']);
            $filesize = $f['filesize'];
            $html .= "<a href=\"./{$name}\">{$name}</a>" . Tools::spaces($name, $max_len) . " " . $uptime . "             " . $filesize . PHP_EOL;
        }
        $html .= '</pre><hr></body></html>';
        $this->setHeader(200, ['Content-Type' => 'text/html']);
        $this->send($html);
        $this->outputStatus = true;
        $files = $dirs = [];
        $html = '';
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
        $len = $header["Content-Length"];
        unset($header["Content-Length"]);
        if (is_array($header) && count($header) > 0) {
            foreach ($header as $k => $v) {
                if ($k == 'Content-Type') {
                    $response .= "{$k}:{$v};charset=UTF-8" . $this->separator;
                } else {
                    $response .= "{$k}:{$v}" . $this->separator;
                }
            }
        }
        $response .= "Content-Length:" . $len . $this->separator;
        $response .= $this->separator;
        return $this->protocolHeader . " " . $this->getHttpCodeValue($code) . $this->separator . $response;
    }


    // 发送状态码
    public function sendCode($code, $headers = [])
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
    public function send($data)
    {
		// 当前字符串大小
        $len =  strlen($data);
		// 如果没有指定Content-Length大小就默认为当前传递过来的字符串大小
		if (!isset($this->headers['Content-Length'])) $this->headers['Content-Length'] = $len;
        // gzip压缩
        if (isset($this->headers['Content-Encoding'])  && $this->headers['Content-Encoding'] == 'gzip') {
            $data = \gzencode($data);
            $this->headers['Content-Length'] = strlen($data);
        }
		// 状态码
        $this->headers['Status'] = $this->headerCode;
        $this->headers = array_merge($this->headers, $this->addHeaders);
        $response =  $this->_getHeader($this->headerCode, $this->headers);
        $response = stripcslashes($response);
        $response .= $data;
        $this->server->send($this->fd, $response);
        $this->clientBody = '';
        $this->clientHeads = [];
        $this->body = '';
        $this->bodyLen = 0;
    }


    // 访问日志
    public function accessLog()
    {
        $log = $_SERVER['REMOTE_ADDR'] . " - - " . date("Y-m-d H:i:s") . " " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['QUERY'] . " " . $_SERVER['SERVER_PROTOCOL'] . " \"" . $_SERVER['User-Agent'] . "\"" . PHP_EOL;
        //echo $log;
        if (!empty($this->accessLogFile) && is_dir(dirname($this->accessLogFile))) {
            file_put_contents($this->accessLogFile, $log, FILE_APPEND);
        }
    }

    // 错误日志
    public function errorLog($log)
    {
        if (!empty($this->errorLogFile) && is_dir(dirname($this->errorLogFile)) && !empty($log)) {
            file_put_contents($this->errorLogFile, $log, FILE_APPEND);
        }
        $this->errorPageShow(502);
        //$this->server->closeStock($this->fd);
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
        // 来处理head头
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
                $v_num = strlen($head2[0] . ":");
                $v2 = substr($v, $v_num);
                $head[trim($head2[0])] = trim($v2);
                $this->clientHeads[trim($head2[0])] = trim($v2);
            }
            /** 检查http头中的字段 **/
            if (!isset($head['If-Modified-Since']) && isset($_SERVER['If-Modified-Since'])) {
                unset($_SERVER['If-Modified-Since']);
            }
            if (!isset($head['Range']) && isset($_SERVER['Range'])) {
                unset($_SERVER['Range']);
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

    // 获取索引文件路径
    public function getDefaultIndex($query)
    {
        $arr = parse_url($query);
        $path =  $arr['path'] ?? '';
        $query2 = isset($arr['query']) ? "?" . $arr['query'] : '';
        if (substr($path, -1) == '/') {
            foreach ($this->defaultIndex as $index) {
                $file = $this->documentRoot . $path . $index;
                if (is_file($file)) {
                    $_SERVER['QUERY'] = $path . $index . "{$query2}";
                    return $file;
                }
            }
        }
        return $this->documentRoot . $path;
    }

    // 访问静态文件
    public function staticDir($file)
    {
        // 这里为了加快速度,缓存一下文件大小
        if (isset($this->files[$file]) || is_file($file)) {
            if (empty($this->types)) {
                $this->types = include(__DIR__ . DIRECTORY_SEPARATOR . 'Type.php');
            }
            $type = $this->types;
            $ext = $this->getExt($file);
            $connect_type = $type[$ext] ?? null;
            $lastTime = date('r');
            $time = time();
            $rangeSize = $this->rangeSize; // 这里是1m
            // 读取文件大小
            if (isset($this->files[$file])) {
                $filesize = $this->files[$file];
            } else {
                $filesize = filesize($file);
            }
            if ($connect_type == null || in_array($connect_type, $this->downTypes)) {
                $headers = ['Content-Length' => $filesize, 'Data' => $lastTime];
                $headers["Content-Type"] = "application/octet-stream";
                $headers["Content-Transfer-Encoding"] = "Binary";
                $headers["Content-disposition"] = "attachment";
                $headers["filename"] = basename($file);
                $code = 200;
                $this->setHeader($code, $headers);
                // 常规的循环读取
                foreach ($this->readForFile($file) as $k => $data) {
                    if ($k == 0) {
                        $response =  $this->_getHeader($code, $headers);
                        $response = stripcslashes($response);
                        $data = $response . $data;
                    }
                    $this->server->send($this->fd, $data);
                }
            } else {
                // 分段传输，针对于大文件
                if (isset($this->clientHeads['Range'])) {
                    $rangeLen = preg_replace('/bytes=(\d+)-/i', '${1}', $this->clientHeads['Range']);
                    $data = $this->readTheFile($file, $rangeLen, $rangeSize);
                    $len = strlen($data);
                    $maxLen = $rangeLen + $len; //这个就是一共传输的字节数
                    /**
                     * 设定文件头的时候一定要注意这里
                     * Content-Length表示本次读取的大小
                     * Content-Range的公式：bytes $rangeLen-($rangeLen+$len-1)/$filesize
                     */
                    $headers = ['Content-Type' => $connect_type, 'Content-Length' => $len, 'Data' => $lastTime, 'Connection' => 'keep-alive', 'Content-Range' => 'bytes ' . $rangeLen . '-' . ($maxLen - 1) . '/' . $filesize];
                    $code = 206;
                } else {
                    // 第一次读取，必须要返回200状态码
                    $data = $this->readTheFile($file, 0, $rangeSize);
                    $len = strlen($data);
                    $headers = ['Content-Type' => $connect_type, 'Content-Length' => $filesize, 'Connection' => 'keep-alive', 'Data' => $lastTime];
                    // 如果读取的文件小于总数，就不开启分片传输
                    if ($len < $filesize) {
                        $headers['Accept-Ranges'] = 'bytes';
                    }
                    $code = 200;
                }
                /** 判断是否开启gzip **/
                if ($this->gzip == 'on' && in_array($connect_type, $this->gzipTypes)) {
                    $headers['Content-Encoding'] = 'gzip'; //deflate';
                }
                $this->setHeader($code, $headers);
                $this->send($data);
            }
            $type = null;
            // 如果大于1000的文件，就重新搞
            if (count($this->files) > 1000) {
                $this->files = [];
            }
            $this->outputStatus = true;
        }
    }

    // 循环打开文件
    public function readForFile($path)
    {
        $handle = fopen($path, "r");
        while (!feof($handle)) {
            yield fread($handle, 65535);
        }
        fclose($handle);
    }

    // 打开文件
    public function readTheFile($path, $start = 0, $length = null)
    {
        $size = filesize($path);
        if ($start < 0) {
            $start += $size;
        }
        if ($length === null) {
            $length = $size - $start;
        }
        if ($length > $size) {
            $length = $size;
        }
        return file_get_contents($path, false, null, $start, $length);
    }

    // 事件绑定
    public function on($event, $callback)
    {
        $event = $this->events[$event] ?? null;
        $this->$event = $callback;
    }
}
