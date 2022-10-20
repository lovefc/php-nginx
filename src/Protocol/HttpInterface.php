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

    public $fd;

    public $bufferSize = 1024*10;

    public $getHeaders;

    public $types;

    public $cacheTime = 10;

    public $files = [];
	
	public $isHand = [];

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
           'Content-Type'=>'text/html;charset=UTF-8',
           'Connection'=>'keep-alive',
           'Content-Encoding'=>'gzip',
        ];
        $this->bodyLen = 0;
    }

    // 启动
    public function start()
    {
        $this->server->start();
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
		//$start_time = microtime(true);
        $this->fd = $fd;
		$this->init();
		$status = $this->handleData($data);
        if($status) return true;
        is_callable($this->onMessage) && call_user_func_array($this->onMessage, [$this, $data]);
    }

    // 关闭
    public function _onClose($fd)
    {
        is_callable($this->onClose) && call_user_func_array($this->onClose, [$fd]);
    }


    // 解析获取的文件头
    public function _getHeader($code, $header = [])
    {
        if (is_array($header) && count($header) > 0) {
			$response = '';
            foreach ($header as $k=>$v) {
                $response .= "{$k}:{$v}".$this->separator;
            }
        }
        $response .= "Content-length:".$this->bodyLen.$this->separator;
        $response .= $this->separator;
        return $this->protocolHeader . " ". $this->getHttpCode($code) . $this->separator . $response;
    }

    // 设置文件头
    public function setHeader($code, $headers = [])
    {
        $this->headerCode = $code;
        $this->headers = $headers;
    }

    // 发送消息
    public function send(string $data, $bodylen=0)
    {
        $response = '';
        if (isset($this->headers['Content-Encoding'])  && $this->headers['Content-Encoding'] == 'gzip') {
            $data = \gzencode($data);
        }
        $this->bodyLen = ($bodylen!= 0) ? $bodylen : strlen($data);
        $response =  $this->_getHeader($this->headerCode, $this->headers);
        $response = stripcslashes($response);
        $response .= $data;
        $this->server->send($this->fd, $response);
        $response = '';
    }

    // 发送状态码
    public function sendCode(string $code)
    {
        $response = $this->separator;
        $response = $this->protocolHeader . " ". $this->getHttpCode($code) . $this->separator.$this->separator;
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


    public function runtime($start_time)
    {
        //$start_time = microtime(true);
        $end_time = microtime(true);
        $thistime = $end_time-$start_time;
        $thistime = round($thistime, 3);
        echo "执行耗时：".($thistime*1000)." 毫秒。".PHP_EOL;
    }

    // 处理数据
    public function handleData($data)
    {
        //有文件头，来处理head头
		//$this->protocolHeader
        if (stripos($data, 'HTTP/1.1')) {
            //echo $data.PHP_EOL;
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
            $_SERVER['DOCUMENT_ROOT'] = getcwd();
            $_SERVER['HEADERS'] = $head;
            $_SERVER['METHOD'] = $method;
            $_SERVER['QUERY'] = $query;
            $head = $head2 = '';		
            return $this->staticDir();
        }
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
            yield fread($handle, 1048576);
        }
        fclose($handle);
    }

    // 静态目录绑定
    public function staticDir()
    {
        
        // 如果是文件
        $file = $_SERVER['DOCUMENT_ROOT'].$_SERVER['QUERY'];
        $arr = parse_url($file);
        $file =  $arr['path'] ?? '';
        if (isset($this->files[$file]) || is_file($file)) {
            if (empty($this->types)) {
                $this->types = include(__DIR__.'/Type.php');			
            }
            $type = $this->types;
            //$this->isStatic = 1;
            $ext = $this->getExt($file);
            $connect_type = $type[$ext] ?? null;
            if ($connect_type) {
                // 获取文件修改时间
                $fileTime = date('r', filemtime($file));
                $since = $_SERVER['HEADERS']['If-Modified-Since'] ?? null;
                $is_cache = 0;
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
                if ($is_cache == 0) {
                    ////'Etag'=>md5($fileTime), //'Cache-Control'=>'max-age=7200'
                    //$expires = date('r',time() + $this->cacheTime);
                    //'Expires'=>$expires
                    //Content-Encoding: gzip
                    $lastTime = date('r');
                    //'Content-Encoding'=>'gzip'
                    $this->setHeader(200, ['Content-type'=>$connect_type,'Last-Modified'=>$lastTime,'Etag'=>md5($fileTime.$file), 'data'=>$lastTime, 'Cache-Control'=>'max-age='.$this->cacheTime]);
					if(isset($this->files[$file])){
                        $filesize = $this->files[$file];
                    }else{
						$filesize = filesize($file);
					}
					// 常规的循环读取
                    foreach ($this->readTheFile($file) as $data) {
                        $this->send($data, $filesize);
                    }
                    
                }
                $type = null;
				// 如果大于1000的文件，就重新搞
				if(count($this->files)>1000){
					$this->files = [];
				}
				return true;
            }
			return false;
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
