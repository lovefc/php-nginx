<?php
namespace FC;

class Worker
{

    public $socket;

    public $onReceive;

    public $onConnect;

    public $onClose;

    public $socketList = [];

    public $protocol;
	
	public $transport;

    public $host;

    public $port;
	
	public $errnol;
	
	public $errmsg;

    // 事件
    private $events = [
        'connect' => 'onConnect',
        'receive' => 'onReceive',
        'close' => 'onClose'
    ];

	// 协议
    private $protocols = [
	    'http'=>'tcp',
		'https'=>'ssl',
		'http2'=>'ssl',
		'ws'=>'tcp',
        'wss'=>'ssl'
	];

    public function __construct($local_socket,$context_option=[])
    {
        $this->config($local_socket);
		$context = [];
		if($context_option){
            $context = stream_context_create($context_option);
            //开启多端口监听,并且实现负载均衡
            stream_context_set_option($context, 'socket', 'so_reuseport', 1);
            // 对于 UDP 套接字，您必须使用STREAM_SERVER_BIND作为flags参数,这段看了(抄自)workerman源码	
		}
		$flags = $this->transport === 'udp' ? STREAM_SERVER_BIND : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;	
        $local_text = 'tcp://'.$this->host.':'.$this->port;
		//echo $this->transport.PHP_EOL;
		//echo $this->protocol.PHP_EOL;
		//die();
		//$flags一个位掩码字段，可以设置为套接字创建标志的任意组合。对于 UDP 套接字，您必须STREAM_SERVER_BIND用作flags参数。
		//$context 8.0 可以为空,在其它版本下，如果不需要设置，则必须设置为一个空数组
        $this->socket = stream_socket_server($local_text,$this->errno, $this->errmsg, $flags, $context);
        stream_set_blocking($this->socket,0); //设置非阻塞
		// 判断资源是否创建成功
		if (is_resource($this->socket)) {
			if(!isset($this->socketList[$this->protocol])){
				$this->socketList[$this->protocol] = [];
			}
            $this->socketList[$this->protocol][(int)$this->socket] = $this->socket;
		}		
        //return $this->socket;
    }
	
	// 配置解析
	public function config($local_socket){
		list($protocol,$host,$port) = explode(":",$local_socket);
        $this->host = substr($host,2);
        $this->port = $port ?? $this->getPort($protocol);
        // 数组中定义的都是tcp协议        
        if(array_key_exists($protocol,$this->protocols)){
            $this->protocol = $protocol;
			$this->transport = $this->protocols[$protocol];
        }else{
			// 其它协议
			$this->protocol = $protocol ?? 'tcp';
            $this->transport = $protocol ?? 'tcp';
        }
	}
    
    // 获取默认端口
    public function getPort($protocol){
        $port = 1993;
        switch($protocol){
            case "http":
                $port = 1993;
            break;
            case "https":
            case "http2":
            case "wss":
                $port = 443;
            break;
        }
        return $port;
    }


    private function accept()
    {
        while (true) {
            $write = $except = [];
            $read = array_filter($this->socketList[$this->protocol]);
			if(!$read) continue;
            stream_select($read,$write,$except,60);
            foreach ($read as $socket){
				$socket === $this->socket ? $this->createSocket() : $this->receive($socket);
				$this->createSocket();
			}
        }
    }

    private function createSocket(){
		$client = call_user_func_array($this->onConnect, [$this->socket]);
		if(is_resource($client)){
			print_r($client);
            $this->socketList[$this->protocol][(int)$client] = $client;
		}
    }

    private function receive($client){
        $buffer = fread($client, 65535);
        if (empty($buffer) && (feof($client) || !is_resource($client))) { fclose($client); unset($this->socketList[$this->protocol][(int)$client]); }
        !empty($buffer) && is_callable($this->onReceive) && call_user_func_array($this->onReceive, [$this->socket, $client, $buffer]);
    }

    public function send($client, $data)
    {
        fwrite($client, $data);
    }

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

