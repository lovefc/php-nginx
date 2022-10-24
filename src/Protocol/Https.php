<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:10:39
 * @LastEditTime : 2022-09-03 02:10:39
 */

namespace FC\Protocol;

class Https extends HttpInterface
{
    public function __construct($text, $context_option=[],$document_root='',$default_index=[])
    {
        $this->server = new \FC\Worker('https://'.$text, $context_option);
        $this->server->on('connect', [$this,"_onConnect"]);
        $this->server->on('receive', [$this,"_onReceive"]);
        $this->server->on('close', [$this,"_onClose"]);
        /** 初始默认 **/
        $this->init();	
		$this->documentRoot =  $document_root;
		$this->defaultIndex = $default_index;
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
        stream_set_blocking($client, false); // 非阻塞模式
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
        if ($client = stream_socket_accept($socket, 0)) {
            $client = $this->https($client);
        }
        restore_error_handler();
        return $client;
    }
	
}
