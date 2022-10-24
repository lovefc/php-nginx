<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-10-24 22:45:54
 */

namespace FC;

class Worker
{
    public $socket;

    public $_readFds = [];
    public $_writeFds = [];
    public $_exceptFds = [];

    public $onReceive;

    public $onConnect;

    public $onClose;

    public $onDecrypt;

    public $socketList = [];

    public $protocol;

    public $transport;

    public $host;

    public $port;

    public $ssl;

    public $buffer = '';

    protected $selectTimeout = 100000000;

    // 事件
    private $events = [
        'connect' => 'onConnect', // 开始
        'receive' => 'onReceive', // 执行
        'decrypt'=>'onDecrypt', //解释ssl
        'close' => 'onClose', // 关闭
    ];

    // 协议
    private $_protocols = [
        'tcp'   => 'tcp',
        'udp'   => 'udp',
        'unix'  => 'unix',
        'ssl'   => 'tcp'
    ];

    private $_transports = [
        'http'=>'tcp',
        'https'=>'ssl',
        'http2'=>'ssl',
        'ws'=>'tcp',
        'wss'=>'ssl',
    ];

    public function __construct($local_socket, $context_option=[])
    {
        $this->stockAddres($local_socket);
        // echo $this->transport.PHP_EOL;
        //echo $this->protocol.PHP_EOL;
        $context = [];
        $context = stream_context_create($context_option);
        // 开启多端口监听,并且实现负载均衡
        stream_context_set_option($context, 'socket', 'so_reuseport', 1);
        // 对于 UDP 套接字，您必须使用STREAM_SERVER_BIND作为flags参数
        $flags = $this->transport === 'udp' ? STREAM_SERVER_BIND : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $local_text = $this->protocol.'://'.$this->host.':'.$this->port;
        //echo $local_text.PHP_EOL;
        //$flags一个位掩码字段，可以设置为套接字创建标志的任意组合。对于 UDP 套接字，您必须STREAM_SERVER_BIND用作flags参数。
        //$context 8.0 可以为空,在其它版本下，如果不需要设置，则必须设置为一个空数组
        $errno = 0;
        $errmsg = '';
        $this->socket = stream_socket_server($local_text, $errno, $errmsg, $flags, $context);
        if (!is_resource($this->socket)) {
            //echo "{$local_socket} 创建成功" . PHP_EOL;
            //} else {
            die("{$local_socket} Creation failed.") . PHP_EOL;
        }

        // ssl 先不进行加密
        if ($this->transport === 'ssl') {
            stream_socket_enable_crypto($this->socket, false);
        }

        // 尝试打开tcp的keepalive，禁用Nagle算法（Nagle算法在未确认数据发送时会将数据放到缓存中。直到得到明显的数据确认或者直到攒到了一定数量的数据后再发包。）
        if (function_exists('socket_import_stream') && $this->transport === 'tcp') {
            set_error_handler(function () {
            });
            $socket = socket_import_stream($this->socket);
            socket_set_option($socket, \SOL_SOCKET, \SO_KEEPALIVE, 1);
            socket_set_option($socket, \SOL_TCP, \TCP_NODELAY, 1);
            restore_error_handler();
        }

        stream_set_blocking($this->socket, false); //设置非阻塞
        stream_set_timeout($this->socket, 5);
        // 判断资源是否创建成功
        if (is_resource($this->socket)) {
            if (!isset($this->socketList[$this->protocol])) {
                $this->socketList[$this->protocol] = [];
            }
            $this->socketList[$this->protocol][(int)$this->socket] = $this->socket;
        }
        return $this->socket;
    }

    // 解析地址
    public function stockAddres($local_socket)
    {
        if (substr_count($local_socket, ':')==3) {
            list($transport, $host, $port) = explode(":", $local_socket);
        } else {
            $port = null;
            list($transport, $host) = explode(":", $local_socket);
        }
        $this->host = substr($host, 2);
        // 常见web协议
        if (array_key_exists($transport, $this->_transports)) {
            $this->transport = $this->_transports[$transport];
            $this->protocol = $this->_protocols[$this->transport] ?? $transport;
        } else {
            // 其它协议
            $this->transport = $transport;
            $this->protocol = $this->_protocols[$transport] ?? $transport;
        }
        $this->port = $port ?? $this->getPort($this->transport);
    }

    // 默认端口
    public function getPort($transport)
    {
        $port = 54321;
        switch($transport) {
            case "ssl":
                $port = 443;
                break;
            case "tcp":
                $port = 80;
                break;
        }
        return $port;
    }

    // 监听
    private function accept()
    {
        while (true) {
            $this->reception();
        }
    }

    private function reception()
    {
        $write = $except = [];
        $read = $this->socketList[$this->protocol];
        $write = $this->_writeFds;
        $except = $this->_exceptFds;
        stream_select($read, $write, $except, 0, $this->selectTimeout);
        foreach ($read as $socket) {
            if ($socket === $this->socket) {
                $this->createSocket();
            } else {
                $this->receive($socket);
            }
        }
        foreach ($write as $fd) {
            //var_dump($fd);
        }
        foreach ($except as $fd) {
            // var_dump($fd);
        }
    }

    // 创建链接
    private function createSocket()
    {
        $client = null;
        if (is_callable($this->onConnect)) {
            $client =  call_user_func_array($this->onConnect, [$this->socket]);
        }
        if (is_resource($client)) {
            $this->socketList[$this->protocol][(int)$client] = $client;
        }
    }

    // 关闭链接
    private function closeStock($client)
    {
        unset($this->socketList[$this->protocol][(int)$client]);
        $close = fclose($client);
        !empty($close) && is_callable($this->onClose) && call_user_func_array($this->onClose, [$client]);
    }

    // 接收处理
    private function receive($client)
    {
        //$client = $this->socketList[$this->protocol][$fd_key] ?? null;
        if (!$client) {
            return false;
        }
        //is_resource($client) &&
        $buffer = fread($client, 65535);
        // 关闭链接
        if (empty($buffer) && (feof($client) || !is_resource($client))) {
            $this->closeStock($client);
        }

        is_callable($this->onReceive) && call_user_func_array($this->onReceive, [$this->socket, $client, $buffer]);
    }

    // 信息发送
    public function send($client, $data)
    {
        if (is_resource($client)) {
            fwrite($client, $data);
        }
    }

    // 事件绑定
    public function on($event, $callback)
    {
        $event = $this->events[$event] ?? null;
        $this->$event = $callback;
    }

    public function start()
    {
        $this->accept();
    }
}
