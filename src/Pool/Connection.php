<?php
namespace FC\Pool;

/** 连接池 **/
class Connection {
	
    public static $pools = [];
	
    public static $poolsize = 10;
	
	/*
    public function __construct($poolsize = 20) {
        // "伪队列"
        $this->poolsize = $poolsize;
        $this->pools = [];
		/*
        for($index = 1; $index <= $this->poolsize; $index ++) {
            //$socket = stream_socket_client('tcp://127.0.0.1:1993', $errorno, $errstr);
            array_push ($this->pools, $socket);
        }
    }
	*/
	
	public static function add($stock){	
		$num = count(self::$pools);
		if($num >= self::$poolsize){
			throw new \ErrorException ("连接池中资源数量已满!");
		}
		return array_push (self::$pools, $stock);
	}

    /**
     * 获取一个链接资源
     */
    public static function getStock() {
        if (count (self::$pools) <= 0) {
            throw new \ErrorException ("连接池中已无链接资源，请稍后重试!");
        } else {
            return array_pop (self::$pools);
        }
    }

    /**
     * 将用过的链接资源放回到数据库连接池
     */
    public static function release($socket) {
        if (count (self::$pools) <= self::$poolsize) {
            array_push (self::$pools, $socket);
        }
    }
}

//$a = new Http(20);

//print_r($a->getStock());