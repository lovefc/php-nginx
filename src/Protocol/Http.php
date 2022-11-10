<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:10:24
 * @LastEditTime : 2022-11-09 01:38:57
 */

namespace FC\Protocol;
use FC\Code\Worker;
class Http extends HttpInterface
{
    public function __construct($text, $context_option=[])
    {	
        $this->server = new Worker('http://'.$text);
        $this->server->on('connect', [$this,"_onConnect"]);
        $this->server->on('receive', [$this,"_onReceive"]);
        $this->server->on('close', [$this,"_onClose"]);
        /** 初始默认 **/
        $this->init();
		$this->requestScheme = 'http';
		$this->getHost($text);
    }

    public function socketAccept($server)
    {
        $client = stream_socket_accept($server, 0, $this->remoteAddress);
        return $client;
    }
}
