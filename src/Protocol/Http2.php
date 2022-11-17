<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:10:24
 * @LastEditTime : 2022-11-09 01:38:36
 */

namespace FC\Protocol;
use FC\Code\Worker;

/**
 * http2规范-https://www.rfc-editor.org/rfc/rfc7540#section-8.1.2.1
 */

class Http2 extends HttpInterface
{
    public function __construct($text, $context_option=[])
    {
        $this->server = new Worker('https://'.$text, $context_option);
        $this->server->on('connect', [$this,"_onConnect"]);
        $this->server->on('receive', [$this,"_onReceive"]);	
        $this->server->on('close', [$this,"_onClose"]);
        /** 初始默认 **/
        $this->init();	
		$this->requestScheme = 'http2';
		$this->getHost($text);
    }
	
    // 初始化参数
    public function init()
    {
        $this->httpCode = 200;
        $this->protocolHeader = 'HTTP/2';
        $this->separator = '\r\n';
        $this->headers = [
           'Content-Type'=>'text/html;charset=UTF-8',
           'Connection'=>'keep-alive'
        ];
    }	
	
	// 交换http2协议
    public function http2(){
		$upgrade = "HTTP/1.1 101 Switching Protocol\r\nUpgrade: h2c\r\nConnection: Upgrade\r\n\r\n";
		$this->server->send($this->fd, $upgrade);
	}	

    // https解密
    public function https($client)
    {
        stream_set_blocking($client, true); // 阻塞模式
        set_error_handler(function () {
        });
        //如果协商失败或没有足够的数据true，false则 返回成功，0您应该重试（仅适用于非阻塞套接字）。
        $type = STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
        $ret = stream_socket_enable_crypto($client, true, $type);
        restore_error_handler();
		// 在低网速的服务器，设置非阻塞可能会造成图片文件无法读取
        //stream_set_blocking($client, false); // 非阻塞模式
        return $client;
    }

    // 获取
    public function socketAccept($server)
    {
        $socket = $server;
        // ssl 先不进行加密
        stream_socket_enable_crypto($socket, false);
        // 第二个参数是无延迟
        set_error_handler(function () {
        });
		
        if ($client = stream_socket_accept($socket, 5, $this->remoteAddress)) {
            $client = $this->https($client);
        }	
        restore_error_handler();
        return $client;
    }
}
