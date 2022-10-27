<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:10:24
 * @LastEditTime : 2022-09-03 02:10:24
 */

namespace FC\Protocol;

class Http extends HttpInterface
{
    public function __construct($text, $context_option=[])
    {
        $this->server = new \FC\Worker('http://'.$text);
        $this->server->on('connect', [$this,"_onConnect"]);
        $this->server->on('receive', [$this,"_onReceive"]);
        $this->server->on('close', [$this,"_onClose"]);
        /** 初始默认 **/
        $this->init();
		$this->requestScheme = 'http';
    }

    public function socketAccept($server)
    {
        $client = stream_socket_accept($server, 1);
        return $client;
    }
}
