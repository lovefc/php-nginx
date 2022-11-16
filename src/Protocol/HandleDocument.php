<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-11-13 19:07:17
 * @LastEditTime : 2022-11-16 16:50:09
 */

namespace FC\Protocol;

/** 
 * 要实现的功能有：
 * 所有的video,image文件分片传输
 * 所有的浏览器不支持的文件下载
 * 所有浏览器支持的文件正常传输
 **/
class HandleDocument
{
    private $httpInterface;

    private $filename = '';

    private $rangeSize = 1000 * 1000 * 1;

    private $connectType = '';

    private $fileSize = 0;

    private $downTypes = [
        'application/zip',
        'application/x-gzip',
        'application/msword',
        'application/octet-stream',
        'application/x-rar'
    ];

    // 实例化本类
    public function __construct($httpInterface, $file, $fileSize, $connectType)
    {
        $this->httpInterface = $httpInterface;
        $this->filename = $file;
        $this->connectType = $connectType;
        $this->fileSize = $fileSize;
    }

    // 访问静态文件
    public function staticDir()
    {
        $file = $this->filename;
        $filesize = $this->fileSize;
        $connect_type = $this->connectType;

        // 如果类型为空就进行下载
        if ($connect_type == null) {
            $connectType = 'application/octet-stream';
            $this->outputFile($file, $filesize, $connectType);
            return true;
        }

        // 判断是不是图片类型的文件，请注意图片文件不能进行分片传输
        if (stristr($connect_type, "images")) {
            $this->outputFile($file, $filesize, $connect_type);
            return true;
        }

        // 如果是音频文件就进行分片
        if (stristr($connect_type, "audio") || stristr($connect_type, "video")) {
            $this->rangeFile($file, $filesize);
            return true;
        }

        // 是指定的类型就进行下载
        if (in_array($connect_type, $this->downTypes)) {
            $connectType = 'application/octet-stream';
            $this->outputFile($file, $filesize, $connectType);
            return true;
        }

        // 都不是，那就输出看看		 
        $this->outputFile($file, $filesize, $connect_type);
        return true;
    }

    // 输出文件
    public function outputFile($file, $filesize, $connectType)
    {
        $lastTime = date('r');
        $headers = ['Content-Length' => $filesize, 'Data' => $lastTime];
        if ($connectType == 'application/octet-stream') {
            $headers["Content-Type"] = "application/octet-stream";
            $headers["Content-Transfer-Encoding"] = "Binary";
            $headers["Content-disposition"] = "attachment";
            $headers["filename"] = basename($file);
        } else {
            $headers = ['Content-Type' => $connectType, 'Content-Length' => $filesize, 'Connection' => 'keep-alive', 'Data' => $lastTime];
        }
        /** 判断是否开启gzip **/
        if ($this->httpInterface->gzip == 'on' && in_array($connectType, $this->httpInterface->gzipTypes)) {
            $headers['Content-Encoding'] = 'gzip';
        }
        $code = 200;
		$headers = array_merge($this->httpInterface->addHeaders,$headers);
        $this->httpInterface->setHeader($code, $headers);
		ob_start();
        foreach ($this->readForFile($file) as $k => $data) {
			echo $data;
        }
		$contents = ob_get_contents();
        ob_end_clean();
		$this->httpInterface->send($contents);
    }

    // 开启分片传输
    public function openRange($code, $headers)
    {
        $headers['Accept-Ranges'] = 'bytes';
        /** 判断是否开启gzip **/
        if ($this->httpInterface->gzip == 'on' && in_array($this->connectType, $this->httpInterface->gzipTypes)) {
            $headers['Content-Encoding'] = 'gzip';
        }
		$headers = array_merge($this->httpInterface->addHeaders,$headers);
        $this->httpInterface->sendCode($code, $headers);
        $this->httpInterface->outputStatus = true;
    }

    // 分段传输
    public function rangeFile($file, $filesize)
    {
        $lastTime = date('r');
        $rangeSize = $this->rangeSize;
        $connect_type = $this->connectType;
        $clientHeads = $this->httpInterface->clientHeads;
        // 分段传输，针对于大文件
        if (isset($clientHeads['Range'])) {
            $rangeLen = preg_replace('/bytes=(\d+)-/i', '${1}', $clientHeads['Range']);
            $data = $this->readTheFile($file, $rangeLen, $rangeSize);
            $len = strlen($data);
            $maxLen = $rangeLen + $len; //这个就是一共传输的字节数
            /**
             * 设定文件头的时候一定要注意这里
             * Content-Length表示本次读取的大小
             * Content-Range的公式：bytes $rangeLen-($rangeLen+$len-1)/$filesize
             */
            $headers = ['Content-Type' => $connect_type, 'Content-Length' => $len, 'Data' => $lastTime, 'Connection' => 'keep-alive', 'Content-Range' => 'bytes ' . $rangeLen . '-' . ($maxLen - 1) . '/' . $filesize];
            $code = 206;
        } else {
            // 第一次读取，必须要返回200状态码
            $data = $this->readTheFile($file, 0, $rangeSize);
            $len = strlen($data);
            $headers = ['Content-Type' => $connect_type, 'Content-Length' => $filesize, 'Connection' => 'keep-alive', 'Data' => $lastTime];
            $code = 200;
            // 如果读取的文件小于总数，就开启分片传输
            if ($len < $filesize) {
                $this->openRange($code, $headers);
                return;
            }
        }
        $this->httpInterface->setHeader($code, $headers);
        $this->httpInterface->send($data);
    }

    // 循环打开文件
    public function readForFile($path)
    {
        $handle = fopen($path, "r");
        while (!feof($handle)) {
            yield fread($handle, $this->rangeSize);
        }
        fclose($handle);
    }

    // 打开文件
    public function readTheFile($path, $start = 0, $length = null)
    {
        $size = filesize($path);
        if ($start < 0) {
            $start += $size;
        }
        if ($length === null) {
            $length = $size - $start;
        }
        if ($length > $size) {
            $length = $size;
        }
        return file_get_contents($path, false, null, $start, $length);
    }
}
