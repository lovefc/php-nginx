<?php

namespace FC;

class Http{

    public $server;
	
	public $body;
	
    // 事件
    private $events = [
        //'send' => 'onSend',
    ];
	
    function __call($name,$arguments = []) {
		//$name = strtolower(substr($name,2));
        if(method_exists($this,$name)){		
            return call_user_func_array($this->$name,$arguments);
		}
    }
	
	public function start(){
		$this->server->start();
	}	
	
    public function on($event, $callback)
    {
        $event = $this->events[$event] ?? null;
        $this->$event = $callback;
    }	
	
    public function __construct($text,$context_option=[])
    {
        $this->server = new Worker('http://'.$text);
        $this->server->on('connect',[$this,"onConnect"]);
        $this->server->on('receive',[$this,"onReceive"]);
        $this->server->on('close',[$this,"onClose"]);
    }
	
    public function onConnect($server){	
        return stream_socket_accept($server,10);
    }

    public function onReceive($server,$fd,$data){
		$data = $data;
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/html;charset=UTF-8\r\n";
        $response .= "Connection: keep-alive\r\n";
        $response .= "Content-length: ".strlen($data)."\r\n\r\n"; //这里会判断是否接受完所有的数据，如果这里的字符和返回的数据不足，则客户端会一直等待
        $response .= $data;		
        $this->server->send($fd,$response);
    }

    public function onClose($fd){
        echo 'onClose '.$fd.PHP_EOL;
    }
}