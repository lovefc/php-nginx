<?php

/**
 * The smallest unit of communication within an HTTP/2 connection, consisting of a header and a variable-length sequence
 * of octets structured according to the frame type.
 *
 * @link https://httpwg.github.io/specs/rfc7540.html
 *
 * @author Martin Schröder
 */
class Frame
{
    /**
     * DATA frames (type=0x0) convey arbitrary, variable-length sequences of octets associated with a stream. One or more DATA frames
     * are used, for instance, to carry HTTP request or response payloads.
     *
     * DATA frames MAY also contain padding. Padding can be added to DATA frames to obscure the size of messages.
     * Padding is a security feature; see Section 10.7.
     */
    public const DATA = 0x00;

    /**
     * The HEADERS frame (type=0x1) is used to open a stream (Section 5.1), and additionally carries a header block fragment.
     * HEADERS frames can be sent on a stream in the "idle", "reserved (local)", "open", or "half-closed (remote)" state.
     */
    public const HEADERS = 0x01;

    /**
     * The PRIORITY frame (type=0x2) specifies the sender-advised priority of a stream (Section 5.3). It can be sent in any
     * stream state, including idle or closed streams.
     */
    public const PRIORITY = 0x02;

    /**
     * The RST_STREAM frame (type=0x3) allows for immediate termination of a stream. RST_STREAM is sent to request cancellation
     * of a stream or to indicate that an error condition has occurred.
     */
    public const RST_STREAM = 0x03;

    /**
     * The SETTINGS frame (type=0x4) conveys configuration parameters that affect how endpoints communicate, such as preferences
     * and constraints on peer behavior. The SETTINGS frame is also used to acknowledge the receipt of those parameters.
     * Individually, a SETTINGS parameter can also be referred to as a "setting".
     */
    public const SETTINGS = 0x04;

    /**
     * The PUSH_PROMISE frame (type=0x5) is used to notify the peer endpoint in advance of streams the sender intends to initiate.
     * The PUSH_PROMISE frame includes the unsigned 31-bit identifier of the stream the endpoint plans to create along with a set of
     * headers that provide additional context for the stream. Section 8.2 contains a thorough description of the use of PUSH_PROMISE frames.
     */
    public const PUSH_PROMISE = 0x05;

    /**
     * The PING frame (type=0x6) is a mechanism for measuring a minimal round-trip time from the sender, as well as determining whether
     * an idle connection is still functional. PING frames can be sent from any endpoint.
     */
    public const PING = 0x06;

    /**
     * The GOAWAY frame (type=0x7) is used to initiate shutdown of a connection or to signal serious error conditions.
     * GOAWAY allows an endpoint to gracefully stop accepting new streams while still finishing processing of previously
     * established streams. This enables administrative actions, like server maintenance.
     */
    public const GOAWAY = 0x07;

    /**
     * The WINDOW_UPDATE frame (type=0x8) is used to implement flow control; see Section 5.2 for an overview.
     */
    public const WINDOW_UPDATE = 0x08;

    /**
     * The CONTINUATION frame (type=0x9) is used to continue a sequence of header block fragments (Section 4.3).
     * Any number of CONTINUATION frames can be sent, as long as the preceding frame is on the same stream and is a HEADERS,
     * PUSH_PROMISE, or CONTINUATION frame without the END_HEADERS flag set.
     */
    public const CONTINUATION = 0x09;

    /**
     * No flags.
     */
    public const NOFLAG = 0x00;

    /**
     * Acknowledged frame.
     */
    public const ACK = 0x01;

    /**
     * When set, bit 0 indicates that this frame is the last that the endpoint will send for the identified stream. Setting this flag
     * causes the stream to enter one of the "half-closed" states or the "closed" state (Section 5.1).
     */
    public const END_STREAM = 0x01;

    /**
     * When set, bit 2 indicates that this frame contains an entire header block (Section 4.3) and is not followed by any CONTINUATION frames.
     */
    public const END_HEADERS = 0x04;

    /**
     * When set, bit 3 indicates that the Pad Length field and any padding that it describes are present.
     */
    public const PADDED = 0x08;

    /**
     * When set, bit 5 indicates that the Exclusive Flag (E), Stream Dependency, and Weight fields are present; see Section 5.3.
     */
    public const PRIORITY_FLAG = 0x20;

    /**
     * The associated condition is not a result of an error. For example, a GOAWAY might include this code to indicate graceful shutdown of a connection.
     */
    public const NO_ERROR = 0x00;

    /**
     * The endpoint detected an unspecific protocol error. This error is for use when a more specific error code is not available.
     */
    public const PROTOCOL_ERROR = 0x01;

    /**
     * The endpoint encountered an unexpected internal error.
     */
    public const INTERNAL_ERROR = 0x02;

    /**
     * The endpoint detected that its peer violated the flow-control protocol.
     */
    public const FLOW_CONTROL_ERROR = 0x03;

    /**
     * The endpoint sent a SETTINGS frame but did not receive a response in a timely manner. See Section 6.5.3 ("Settings Synchronization").
     */
    public const SETTINGS_TIMEOUT = 0x04;

    /**
     * The endpoint received a frame after a stream was half-closed.
     */
    public const STREAM_CLOSED = 0x05;

    /**
     * The endpoint received a frame with an invalid size.
     */
    public const FRAME_SIZE_ERROR = 0x06;

    /**
     * The endpoint refused the stream prior to performing any application processing (see Section 8.1.4 for details).
     */
    public const REFUSED_STREAM = 0x07;

    /**
     * Used by the endpoint to indicate that the stream is no longer needed.
     */
    public const CANCEL = 0x08;

    /**
     * The endpoint is unable to maintain the header compression context for the connection.
     */
    public const COMPRESSION_ERROR = 0x09;

    /**
     * The connection established in response to a CONNECT request (Section 8.3) was reset or abnormally closed.
     */
    public const CONNECT_ERROR = 0x0A;

    /**
     * The endpoint detected that its peer is exhibiting a behavior that might be generating excessive load.
     */
    public const ENHANCE_YOUR_CALM = 0x0B;

    /**
     * The underlying transport has properties that do not meet minimum security requirements (see Section 9.2).
     */
    public const INADEQUATE_SECURITY = 0x0C;

    /**
     * The endpoint requires that HTTP/1.1 be used instead of HTTP/2.
     */
    public const HTTP_1_1_REQUIRED = 0x0D;

    /**
     * Frame type.
     *
     * @var int
     */
    public $type;

    /**
     * Stream identifier (0 for connection).
     * 
     * @var int
     */
    public $stream;
    
    /**
     * Frame flags.
     *
     * @var int
     */
    public $flags;

    /**
     * Payload of the frame.
     *
     * @var string
     */
    public $data;

    /**
     * Create a new HTTP/2 frame.
     *
     * @param int $type
     * @param string $data
     * @param int $flags
     */
    public function __construct(int $type, int $stream, string $data, int $flags = self::NOFLAG)
    {
        $this->type = $type;
        $this->stream = $stream;
        $this->data = $data;
        $this->flags = $flags;
    }
    
    public function __debugInfo(): array
    {
        return [
            'type' => $this->getTypeName(),
            'flags' => $this->flags,
            'stream' => $this->stream,
            'length' => \strlen($this->data)
        ];
    }

    /**
     * Convert frame into a human-readable form.
     */
    public function __toString(): string
    {
        $info = ($this->type == self::WINDOW_UPDATE) ? \unpack('N', $this->data)[1] : (\strlen($this->data) . ' bytes');
        
        return \sprintf("%s [%b] <%u> %s", $this->getTypeName(), $this->flags, $this->stream, $info);
    }
    
    /**
     * Get a human-readable label that represents the frame type.
     */
    public function getTypeName(): string
    {
        switch ($this->type) {
            case self::CONTINUATION:
                return 'CONTINUATION';
            case self::DATA:
                return 'DATA';
            case self::GOAWAY:
                return 'GOAWAY';
            case self::HEADERS:
                return 'HEADERS';
            case self::PING:
                return 'PING';
            case self::PRIORITY:
                return 'PRIORITY';
            case self::PUSH_PROMISE:
                return 'PUSH_PROMISE';
            case self::RST_STREAM:
                return 'RST_STREAM';
            case self::SETTINGS:
                return 'SETTINGS';
            case self::WINDOW_UPDATE:
                return 'WINDOW_UPDATE';
        }
        
        return 'UNKNOWN';
    }

    /**
     * Encode the frame into it's binary form for transmission.
     *
     * @return string
     */
    public function encode(): string
    {
        return \substr(\pack('NccN', \strlen($this->data), $this->type, $this->flags, $this->stream), 1) . $this->data;
    }

    /**
     * Get frame payload (data with padding removed).
     *
     * @return string
     */
    public function getPayload(): string
    {
        if ($this->flags & self::PADDED) {
            return \substr($this->data, 1, -1 * \ord($this->data[0]));
        }
        
        return $this->data;
    }
}





class stock
{

    private $transport = 'tcp';

    private $errno = '';

    private $errmsg = '';

    private $host = '0.0.0.0';

    private $port = '8080';

    private $context_option = [];

    private $socket;

    private $local_socket = '';

    public $connections = [];

    private $read = [];

    private $write = null;

    private $except = null;

    private $sslCompleted = [];
	
	private $isHand = array();// 存储状态


    public function __construct($host, $port, $context_option = [])
    {
        $this->host = $host;
        $this->port = $port;
        $this->context_option = $context_option;
        $this->transport = $port === 443 ? 'ssl' : 'tcp';
        $this->tcp();
    }

    public function tcp()
    {
        $context = stream_context_create($this->context_option);
        //开启多端口监听,并且实现负载均衡
        stream_context_set_option($context, 'socket', 'so_reuseport', 1);
        // 对于 UDP 套接字，您必须使用STREAM_SERVER_BIND作为flags参数,这段看了(抄自)workerman源码
        $flags = $this->transport === 'udp' ? STREAM_SERVER_BIND : STREAM_SERVER_BIND | STREAM_SERVER_LISTEN;
        $this->local_socket = "tcp://{$this->host}:{$this->port}";
        echo $this->local_socket . PHP_EOL . PHP_EOL;
        $this->socket = stream_socket_server($this->local_socket, $this->errno, $this->errmsg, $flags, $context);
        if (is_resource($this->socket)) {
            echo "{$this->host}:{$this->port}创建成功" . PHP_EOL;
        } else {
            die("创建失败");
        }
        // ssl 先不进行加密
        if ($this->transport === 'ssl') {
            stream_socket_enable_crypto($this->socket, false);
        }

        // 为资源流设置阻塞或者阻塞模式,false为非阻塞
        stream_set_blocking($this->socket, false);
    }

    public function https($client)
    {
        if (!is_resource($client)) {
            return false;
        }
        if (\feof($client)) {
            $this->close($client);
            return false;
        }
		
        stream_set_blocking($client, false); // 非阻塞
		
        set_error_handler(function () {});
        $type = STREAM_CRYPTO_METHOD_TLSv1_1_SERVER | STREAM_CRYPTO_METHOD_TLSv1_2_SERVER;
		//如果协商失败或没有足够的数据返回false ，0您应该重试（仅适用于非阻塞套接字）。
        $ret = stream_socket_enable_crypto($client, true, $type);
		//var_dump($ret);
        restore_error_handler();
        if (false === $ret) {
            $this->close($client);
            return false;
        } elseif (0 === $ret) {
            return 0;
        }
        return true;
    }

    // 解密ssl
    public function decodeSSL()
    {
        $read = $this->connections;
        if (!$read) return false;
        foreach ($read as $k => $client) {
			if(isset($this->sslCompleted[$k]) && $this->sslCompleted[$k] === true){
				continue;
			}
			$this->sslCompleted[$k] = false;
            if ($this->transport === 'ssl') {
                do {
                    $dssl = $this->https($client);
                } while (($dssl === 0));
                if ($dssl === true) {
                    $this->sslCompleted[$k] = true;
                }
            } else {
                $this->sslCompleted[$k] = true;
            }
        }
		//var_dump($this->sslCompleted);
    }

    public function run2()
    {
        $socket = $this->socket;
        // 第二个参数是无延迟
        set_error_handler(function () {
        });
        stream_set_blocking($socket, false);
        if ($client = stream_socket_accept($socket, 0)) {     //empty($this->connections) ? -1 : 0)
            $this->connections[] = $client;
        } //else{
			//usleep(100);
		//}
        restore_error_handler();
    }

    // 定时功能
    public function calctimeout($maxtime, $starttime)
    {
        return ($maxtime - ((microtime(true) - $starttime) * 1000000)) / 1000000;
    }
    // 运行
    public function run()
    {
        $read = $this->connections;
        if (!$read) return false;
        set_error_handler(function () {
        });
        $maxtime = 2000000;
        $starttime = microtime(true);
        $flags = stream_select($read, $this->write, $this->except, 0);
        restore_error_handler();
        if ($flags) {
            $this->steam($read);
        }
    }
    /**
     * 关闭一个客户端连接
     */
    public function close($sock)
    {
        $key = array_search($sock, $this->connections);
        fclose($sock);
	    unset($this->sslCompleted[$key]);
        unset($this->connections[$key]);
    }
    // 获取内容
    public function get_contents($handle, $timeout_seconds = 0.5)
    {
        $ret = "";

        // feof ALSO BLOCKS:
        // while(!feof($handle)){$ret.=stream_get_contents($handle,1);}
        while (true) {
            $starttime = microtime(true);
            $new = stream_get_contents($handle, 1);
            $endtime = microtime(true);
            if (is_string($new) && strlen($new) >= 1) {
                $ret .= $new;
            }
            $time_used = $endtime - $starttime;
            // var_dump('time_used:',$time_used);
            if (($time_used >= $timeout_seconds) || !is_string($new) ||
                (is_string($new) && strlen($new) < 1)
            ) {
                break;
            }
        }
        return $ret;
    }

    public function steam($read)
    {
        $data = '';
        foreach ($read as $k => $c) {
			// 第一次握手
			if (!$this->isHand[$key]) {
                //$buffer = fread($c, 1024 * 10);	
                //var_dump($buffer);				
                $this->doHandshake($c, '', $key);
				break;
            }			
						
			$key = array_search($c, $this->connections); // 搜索套接字
            if (!isset($this->sslCompleted[$k]) || $this->sslCompleted[$k] === false) {
                break;
            }
			
            stream_set_blocking($c, false);
            //$key = array_search($c, $connections);
            $ip = stream_socket_get_name($c, false);
            // 传输中断了,或者是其它原因,那么就关闭中断这个客户端链接
            //如果文件指针到了 EOF 或者出错时则返回 true，否则返回一个错误（包括 socket 超时），其它情况则返回 false。
			/*
            if (feof($c)) {
                $this->close($c);
                continue;
            }
			*/
            // 在给定的流上设置读取文件缓冲,如果第二个参数为false,那么就无缓冲
            if (function_exists('stream_set_read_buffer')) {
               // stream_set_read_buffer($c, false);
            }
            // 在给定的流上设置写入文件缓冲,如果第二个参数为false,那么就无缓冲
            if (function_exists('stream_set_write_buffer')) {
               // stream_set_write_buffer($c, false);
            }
            // 读取这个资源流
            $buffer = false; 
            $size = $size2 = 0; 
            //stream_set_timeout($c,10);
            do {
                $buffer = fread($c, 1024 * 10);
                $size = strlen($buffer);
				$size2 += $size;
                //echo $size2.PHP_EOL;
                $data .= $buffer;
            } while (($size && $size > 1));
			var_dump($c);
            //$data = fread($c, 1024*10)
			//var_dump($size);
            if ($size == 0) {
				//file_put_contents(__DIR__.'/1.txt',$data,LOCK_EX);
				$str = ":method:get\r\nscheme:https\r\n:authority:127.0.0.1\r\n:path:/\r\n";
                $str = "HTTP/2 200 OK\r\nDate: Mon, 27 Jul 2022 12:28:53 GMT\r\nServer: lovefc\r\nContent-Type: text/plain\r\n\r\nhello";
				//$data = $this->getMsg($str);
                fwrite($c, $str, strlen($str));
                $this->close($c);
                $data = null;
            }
        }
    }
	
    /**
     * 首次与客户端握手
     */
    public function doHandshake($sock, $data, $key)
    {
		/*
		$sets = [];
        $settings = '';
		foreach ($sets as $k => $v) {
            $settings .= pack('nN', $k, $v);
        }

        $frame = (new Frame(Frame::SETTINGS, 0, $settings))->encode();
		$frame = rtrim(strtr(base64_encode($frame), [
            '+' => '-',
            '/' => '_'
        ]), '=');
        */
        $upgrade = implode("\r\n", [
            'HTTP/1.1 101 Switching Protocols',
            'Connection: Upgrade',
            'Upgrade: h2c'
        ]) . "\r\n\r\n";
        fwrite($sock, $upgrade, strlen($upgrade));
        $this->isHand[$key] = true;
    }
	
    /**
     * 解码过程
     */
    public function decode($buffer)
    {
		// 变量初始化
        $len = $masks = $data = $decoded = null;
		
		// ord() 函数返回字符串的首个字符的 ASCII 值。
		// 计算二进制
        $len = ord($buffer[1]) & 127;
		
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else if ($len === 127) {
            $masks = substr($buffer, 10, 4);
            $data = substr($buffer, 14);
        } else {
            $masks = substr($buffer, 2, 4);
            $data = substr($buffer, 6);
        }
        for ($index = 0; $index < strlen($data); $index++) {
            $decoded .= $data[$index] ^ $masks[$index % 4];
        }
        return $decoded;
    }

    /**
     * 编码过程
     */
    public function encode($buffer)
    {
        $length = strlen($buffer);
        if ($length <= 125) {
            return "\x81" . chr($length) . $buffer;
        } else if ($length <= 65535) {
            return "\x81" . chr(126) . pack("n", $length) . $buffer;
        } else {
            return "\x81" . char(127) . pack("xxxxN", $length) . $buffer;
        }
    }	
	
	// 获取消息，执行回调
    public function getMsg($buffer, $callback='')
    {
        // 先解码
        $data = $this->decode($buffer);
		// 执行回调
        if (!empty($callback)) {
            $data = call_user_func($callback, $data);
        }
		// 编码消息，用于返回
        $data = $this->encode($data);
        return $data;
    }	
	
}


$host = '0.0.0.0';

$port = 443;

$context_option = array(
    'ssl' => array(
        'local_cert'  => __DIR__ . '/server.crt', // 也可以是crt文件
        'local_pk'    => __DIR__ . '/server.key',
        'verify_peer' => false, // 是否需要验证 SSL 证书,默认为true
    )
);
//$context_option = [];

function run()
{
    global $host;
    global $port;
    global $context_option;
    for ($n = 0; $n <= 4; $n++) {
        if (($pid = pcntl_fork()) == 0) {
            $s = [];
            for ($n2 = 1; $n2 <= 4; $n2++) {
                $s[] = new stock($host, $port, $context_option);
            }
            while (1) {
                foreach ($s as $k => $v) {
                    $v->run2();
                    $v->run();
                }
            }
        }
        echo "子进程已创建" . PHP_EOL;
    }
}

//run();

$v = new stock($host, $port, $context_option);
while (1) {
    $v->run2();
    $v->decodeSSL();
    $v->run();
    //usleep(1000);
}
			
/*
$maxtime = 200000;
$starttime = microtime(true);

function calctimeout($maxtime, $starttime)
{
   return ($maxtime - ((microtime(true) - $starttime) * 1000000))/1000000;
}


echo calctimeout($maxtime, $starttime);
*/

/*

$v = new stock($host,$port);

while(1){
   $v->run2();
   $v->run();
   usleep(1000);
}

*/
/*
$s = [];
for ($n = 1; $n <= 10; $n++) {
    $s[] = new stock();
}

while (1) {
    foreach ($s as $k => $v) {
        $v->run2();
        $v->run();
    }
}
*/

/*
$pid = pcntl_fork();
//父进程和子进程都会执行下面代码
if ($pid == -1) {
    //错误处理：创建子进程失败时返回-1.
     die('could not fork');
} else if ($pid) {
	 echo "主进程".PHP_EOL;
     pcntl_wait($status); //等待子进程中断，防止子进程成为僵尸进程。
} else {
	echo "子进程".PHP_EOL;
}
*/