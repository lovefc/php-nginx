<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2024-07-29 15:21:23
 */

namespace FC\Protocol;

use FC\Code\Client as fpmClient;
use FC\Code\ErrorHandler;
use FC\Code\HttpCode;
use FC\Code\NginxConf;
use FC\Code\Tools;

class HttpInterface
{
    use ErrorHandler;

    public $protocolHeader = 'HTTP/1.1';

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

    public $rewrite;

    public $accessLogFile = '';

    public $errorLogFile = '';

    public $remoteAddress;

    public $clientBody = '';

    private $fpmClient;

    private $firstRead = 0; // 首次读取

    // 事件
    private $events = [
        'connect' => 'onConnect',
        'message' => 'onMessage',
        'close' => 'onClose',
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
        $this->documentRoot = str_ireplace("\\", "/", $this->documentRoot);
        $this->defaultIndex = NginxConf::$Configs[$server_name]['index'] ?? [];
        $this->displayCatalogue = NginxConf::$Configs[$server_name]['autoindex'][0] ?? 'off';
        $this->gzip = NginxConf::$Configs[$server_name]['gzip'][0] ?? 'off';
        $this->gzipCompLevel = NginxConf::$Configs[$server_name]['gzip_comp_level'][0] ?? 2;
        $this->gzipTypes = NginxConf::$Configs[$server_name]['gzip_types'] ?? [];
        $this->addHeaders = NginxConf::$Configs[$server_name]['add_header'] ?? [];
        $this->errorPage = NginxConf::$Configs[$server_name]['error_page'] ?? '';
        $this->locations = NginxConf::$Configs[$server_name]['location'] ?? '';
        $this->rewrite = NginxConf::$Configs[$server_name]['rewrite'] ?? '';
        $this->accessLogFile = NginxConf::$Configs[$server_name]['access_log'][0] ?? '';
        $this->errorLogFile = NginxConf::$Configs[$server_name]['error_log'][0] ?? '';
        $_SERVER['DOCUMENT_ROOT'] = $this->documentRoot;
        $_SERVER['SERVER_SOFTWARE'] = 'php-nginx/0.01';
        $_SERVER['REQUEST_SCHEME'] = $this->requestScheme;
        $_SERVER['SERVER_PROTOCOL'] = $this->protocolHeader;
        if ($this->requestScheme == 'https') {
            $_SERVER['HTTPS'] = 'on';
            $_SERVER['SERVER_PROTOCOL'] = 'HTTP/2.0';
        }
        $_SERVER['HTTP_HOST'] = $server_name;
        $_SERVER['SERVER_NAME'] = $server_name;
        $_SERVER['GATEWAY_INTERFACE'] = 'CGI/1.1';
        $address = explode(":", $this->remoteAddress);
        $_SERVER['REMOTE_ADDR'] = $address[0] ?? '';
        $_SERVER['REMOTE_PORT'] = $address[1] ?? '';
        //$_SERVER['PATH_INFO'] =  $_SERVER['ORIG_PATH_INFO'] = '';
        $this->explodeQuery();
    }

    // 解析query
    public function explodeQuery($query = null)
    {
        $query = $query ?? $_SERVER['QUERY'];
        $tmp = explode("?", $query);
        $url = isset($tmp[0]) ? trim($tmp[0]) : '';
        $_SERVER['DOCUMENT_URI'] = $_SERVER['PHP_SELF'] = $url;
        $_SERVER['QUERY_STRING'] = isset($tmp[1]) ? trim($tmp[1]) : '';
        $file = $this->documentRoot . $url;
        // 判断是文件
        if (is_file($file)) {
            $_SERVER['SCRIPT_FILENAME'] = $this->documentRoot . $url;
            $_SERVER['PATH_TRANSLATED'] = $this->documentRoot . $url;
            $_SERVER['SCRIPT_NAME'] = $url;
            return false;
        }
        $pattern = '/(.*\.php)(.*)/i';
        $match = preg_match($pattern, $url, $matches);
        if (isset($matches[1])) {
            $_SERVER['SCRIPT_NAME'] = $matches[1];
        } else {
            $_SERVER['SCRIPT_NAME'] = $_SERVER['PHP_SELF'];
        }
        if (isset($matches[2])) {
            $_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'] = $matches[2];
        }

        $_SERVER['SCRIPT_FILENAME'] = $this->documentRoot . $_SERVER['SCRIPT_NAME'];
        $_SERVER['PATH_TRANSLATED'] = $this->documentRoot . $_SERVER['REQUEST_URI'];
        return true;
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

    // 伪静态规则解析
    public function _rewrite()
    {
        if ($this->rewrite) {
            $value = $this->rewrite[0] ?? '';
            $value2 = $this->rewrite[1] ?? '';
            $value = str_replace('/', '\\/', $value);
            $url = $_SERVER['DOCUMENT_URI'] . '?' . $_SERVER['QUERY_STRING'];
            $pattern = '/(.*\.php)(.*)/i';
            $match = preg_match($pattern, $url, $m);
            if (preg_match("/{$value}/i", $url, $matches) && !isset($m[0])) {
                $url = trim($url, "/");
                // 伪静态模式下：/index.php/archives/1
                $result = preg_replace('/\$args/i', $url, $value2);
                $this->explodeQuery($result);
            }
        }
    }

    // 处理
    public function _onReceive($server, $fd, $data)
    {
        $this->fd = $fd;
        $this->init();
        $this->outputStatus = false;
        // 删除文件判断缓存
        clearstatcache();
        $this->handleData($data);
        $tmp = explode(":", $_SERVER['Host'])[0];
        $this->setEnv($tmp);
        $query = IS_WIN === true ? mb_convert_encoding($_SERVER['QUERY'], "GBK", "UTF-8") : $_SERVER['QUERY'];
        $file = $this->getDefaultIndex($query);
        $flag = $this->explodeQuery();
        if ($flag) {
            $this->_rewrite();
        }
        !$this->outputStatus && $this->analysisLocation($_SERVER['SCRIPT_NAME']);
        !$this->outputStatus && $this->staticDir($file);
        !$this->outputStatus && $this->displayCatalogue == 'on' && $this->autoIndex($file);
        !$this->outputStatus && $this->errorPageShow(404);
        $this->accessLog();
    }

    // 错误页面
    public function errorPageShow($code)
    {
        if ($this->errorPage) {
            foreach ($this->errorPage as $k => $v) {
                if ($k == $code) {
                    if (Tools::checkUrl($v)) {
                        $this->sendCode('302', ['Location' => $v]);
                        $this->outputStatus = true;
                        return;
                    } else {
                        if (is_file($v)) {
                            $this->staticDir($v);
                            return;
                        }
                    }
                }
            }
        }
        $text = $this->getHttpCodeValue($code);
        $data = '<html><head><title>' . $text . '</title></head><body><center><h1>' . $text . '</h1></center><hr><center>php-nginx/0.01</center></body></html>';
        $this->setHeader($code);
        $this->send($data);
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
            'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'],
            'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'],
            'CONTENT_TYPE' => $_SERVER['Content-Type'] ?? '',
            'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
            'DOCUMENT_ROOT' => $this->documentRoot,
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
            'PATH_INFO' => $_SERVER['PATH_INFO'] ?? '',
            'ORIG_PATH_INFO' => $_SERVER['ORIG_PATH_INFO'] ?? '',
            'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? '',
            'HTTP_COOKIE' => $_SERVER['Cookie'] ?? '',
            'REQUEST_TIME' => time(),
            'HTTP_REFERER' => $_SERVER['Referer'] ?? '',
            'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ?? 'http',
            'HTTPS' => $_SERVER['HTTPS'] ?? '',
            'SERVER_PROTOCOL' => $_SERVER['SERVER_PROTOCOL'] ?? '',
        ];
        $server = array_filter(array_merge($this->clientHeads, $server));
        //$client->setKeepAlive(true); // 长连接
        //$client->setPersistentSocket(true);
        $client->setConnectTimeout(5000);
        $client->setReadWriteTimeout(5000);
        $text = $client->request($server, $content);
        if (empty($text)) {
            $this->errorPageShow(502);
            $this->outputStatus = true;
            return;
        }
        $arr = explode("\r\n\r\n", $text);
        $header_text = $arr[0] ?? [];
        unset($arr[0]);
        $content = implode("\r\n\r\n", $arr);
        // 错误报告
        if (strstr($header_text, "PHP message:")) {
            $tmp = explode("\n", $header_text);
            $tmp2 = preg_split("/(Status:|Content-Type:)+/i", $tmp[0]);
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
            $head2 = explode(":", $v);
            $v_num = strlen($head2[0] . ":");
            $v2 = trim(substr($v, $v_num));
            $k2 = trim($head2[0]);
            // cookies处理
            if ($k2 == 'Set-Cookie') {
                $_headers[] = 'Set-Cookie:' . $v2;
            } else {
                $_headers[$k2] = $v2;
            }
        }
        /** 这里要获取到fpm里面设置的状态码和header头 **/
        $code = isset($_headers['Status']) ? $this->getHttpCode($_headers['Status']) : 200;
        $_headers['Content-Length'] = strlen($content);
        $this->setHeader($code, $_headers);
        $this->send($content);
        $server = [];
        $content = '';
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

    // 转化header头
    public function headerTostr($heads)
    {
        $arrs = [];
        if (is_array($heads) && count($heads) > 0) {
            foreach ($heads as $k => $v) {
                if (is_numeric($k)) {
                    $arrs[] = $v;
                } else {
                    $arrs[] = $k . ':' . $v;
                }
            }
        }
        return $arrs;
    }

    // 解析参数
    public function analysisLocationValue($text)
    {
        if (!is_array($text)) {
            $text = str_replace(";", '', $text);
            $arr = array_values(array_filter(explode(" ", trim($text))));
            $key = $arr[0] ?? '';
            $value = $arr[1] ?? '';
        } else {
            $arr = implode("", $text);
            $arr = array_values(array_filter(explode(" ", $text)));
        }
        $key = $arr[0] ?? '';
        $value = $arr[1] ?? '';
        // 判断文件缓存
        if (strtolower($key) == 'expires') {
            $time = Tools::timeConversion($value);
            if ($this->cacheFile($time)) {
                return true;
            }
            $lastTime = date('r');
            $heads = ['Last-Modified' => $lastTime, 'Cache-Control' => 'max-age=' . $time];
            $this->addHeaders = array_merge($this->addHeaders, $heads);
        }
        // 判断返回值
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
        // 判断php执行脚本
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
                $path = $dir . "/" . $filename;
                if (is_file($path)) {
                    $files[$i]['filename'] = $filename;
                    $files[$i]['uptime'] = filemtime($path);
                    $filename = mb_convert_encoding($filename, "GBK", "UTF-8"); // iconv('utf-8', 'gb2312', $filename);
                    $len = strlen($filename);
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
        foreach ($dirs as $d) {
            $html .= "<a href=\"./{$d}\">{$d}</a>" . Tools::spaces($d, $max_len) . " -" . PHP_EOL;
        }
        foreach ($files as $k => $f) {
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
        //$len = isset($header["Content-Length"]) ?? 0;
        //unset($header["Content-Length"]);
        if (is_array($header) && count($header) > 0) {
            foreach ($header as $k => $v) {
                if ($k == 'Content-Type') {
                    $response .= "{$k}:{$v}" . $this->separator;
                } else {
                    $response .= "{$v}" . $this->separator;
                }
            }
        }
        //$response .= "Content-Length:" . $len . $this->separator;
        $response .= $this->separator;
        return $this->protocolHeader . " " . $this->getHttpCodeValue($code) . $this->separator . $response;
    }

    // 发送状态码
    public function sendCode($code, $headers = [])
    {
        // 重新设置header头和状态码
        $this->setHeader($code, $headers);
        $headers2 = $this->headerTostr($this->headers);
        $response = $this->_getHeader($this->headerCode, $headers2);
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
        $len = strlen($data);
        // 如果没有指定Content-Length大小就默认为当前传递过来的字符串大小
        if (!isset($this->headers['Content-Length'])) {
            $this->headers['Content-Length'] = $len;
        }

        // gzip压缩
        if (isset($this->headers['Content-Encoding']) && $this->headers['Content-Encoding'] == 'gzip') {
            $data = \gzencode($data);
            $this->headers['Content-Length'] = strlen($data);
        }

        // 状态码
        $this->headers['Status'] = $this->headerCode;
        $headers = $this->headerTostr($this->headers);
        $response = $this->_getHeader($this->headerCode, $headers);
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
        if (stripos($data, $this->protocolHeader)) {
            $buffer = explode("\r\n\r\n", $data);
            $data2 = $buffer[0] ?? '';
            unset($buffer[0]);
            if ($this->firstRead == 1) {
                $this->clientBody .= implode("\r\n\r\n", $buffer);
            } else {
                $this->clientBody = implode("\r\n\r\n", $buffer);
            }
            $header = explode("\r\n", $data2);
            $arr = explode(" ", $header[0]);
            $method = $arr[0] ?? '';
            $query = $arr[1] ?? '';
            $protocolHeader = $arr[2] ?? '';
            unset($header[0]);
            $head = [];
            // 这里，修复了时间戳的问题,不可只用:号来分割
            foreach ($header as $v) {
                $head2 = explode(":", $v);
                $v_num = strlen($head2[0] . ":");
                $v2 = substr($v, $v_num);
                $k2 = trim($head2[0]);
                $head[$k2] = trim($v2);
                $this->clientHeads[$k2] = trim($v2);
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
        }
        // 为空
        if (!isset($_SERVER['Content-Length'])) {
            $this->firstRead = 0;
            return true;
        }

        if (strlen($this->clientBody) == $_SERVER['Content-Length']) {
            $this->firstRead = 0;
            return true;
        }
        return true;
    }

    // 获取索引文件路径
    public function getDefaultIndex($query)
    {
        $arr = parse_url($query);
        $path = $arr['path'] ?? '';
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

    // 获取文件后缀
    public function getExt($filename)
    {
        $arr = pathinfo($filename);
        $ext = $arr['extension'];
        return strtolower($ext);
    }

    // 访问静态文件
    public function staticDir($file)
    {
        // 这里为了加快速度,缓存一下文件大小
        if (isset($this->files[$file]) || is_file($file)) {
            if (empty($this->types)) {
                $this->types = include __DIR__ . "/" . 'Type.php';
            }
            $type = $this->types;
            $ext = $this->getExt($file);
            $connectType = $type[$ext] ?? null;
            // 读取文件大小
            if (isset($this->files[$file])) {
                $fileSize = $this->files[$file];
            } else {
                $fileSize = filesize($file);
                $this->files[$file] = $fileSize;
            }
            $handleDocument = new HandleDocument($this, $file, $fileSize, $connectType);
            if ($handleDocument->staticDir()) {
                $this->outputStatus = true;
            }
            $handleDocument = '';
            // 如果大于1000的文件，就重新搞
            if (count($this->files) > 1000) {
                $this->files = [];
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
