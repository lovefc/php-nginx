<?php

namespace FC;

class Https{

    public $server;
	
	public $connections;

    public function __construct($text,$context_option=[])
    {
        $this->server = new Worker('https://'.$text,$context_option);
        $this->server->on('connect',[$this,"onConnect"]);
        $this->server->on('receive',[$this,"onReceive"]);
        $this->server->on('close',[$this,"onClose"]);
    }
	
	public function start(){
		$this->server->start();
	}		
	
    public function on($event, $callback)
    {
        $event = $this->events[$event] ?? null;
        $this->$event = $callback;
    }
	
    public function onConnect($server){
        return $this->chuli($server);
    }


    public function https($client)
    {
        stream_set_blocking($client, true); // 阻塞模式
        set_error_handler(function () {
        });
        //如果协商失败或没有足够的数据true，false则 返回成功，0您应该重试（仅适用于非阻塞套接字）。
        $type = STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
        $ret = stream_socket_enable_crypto($client, true, $type);
        restore_error_handler();
		stream_set_blocking($client, false); // 非阻塞模式
		return $client;
    }
	
	public function chuli($server){
		$socket = $server;
        // ssl 先不进行加密
        stream_socket_enable_crypto($socket, false);
        // 第二个参数是无延迟
        set_error_handler(function () {});
        if ($client = stream_socket_accept($socket,0)) {
            $client = $this->https($client);
        }
        restore_error_handler();		
        return $client;
	}

	
    public function onReceive($server,$fd,$data){
		//echo $data.PHP_EOL;	
        $data = $data;
        $response = "HTTP/1.1 200 OK\r\n";
        $response .= "Content-Type: text/html;charset=UTF-8\r\n";
        $response .= "Connection: keep-alive\r\n";
        $response .= "Content-length: ".strlen($data)."\r\n\r\n";
        $response .= $data; 	
        $this->server->send($fd,$response);
    }

    public function onClose($fd){
        echo 'onClose '.$fd.PHP_EOL;
    }
}