<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-08-23 23:47:56
 * @LastEditTime : 2022-08-23 23:48:33
 */
namespace FC;

class SSL
{
	
	public $local_cert = '';
	
	public $local_pk = '';
	
	public function __construct($local_cert,$local_pk)
    {
		$this->local_cert = $local_cert;
	    $this->local_pk = $local_pk;
	}
	
    public function sign($data)
    {
        //读取私钥文件
        $priKey = file_get_contents($this->local_cert);

        //转换为openssl密钥，必须是没有经过pkcs8转换的私钥
        $res = openssl_get_privatekey($priKey);

        //调用openssl内置签名方法，生成签名$sign
        openssl_sign($data, $sign, $res);

        //释放资源
        openssl_free_key($res);

        return $sign;
    }

    public function verify($data, $sign)
    {
        //读取公钥文件
        $pubKey = file_get_contents($this->local_cert);

        //转换为openssl格式密钥
        $res = openssl_get_publickey($pubKey);

        //调用openssl内置方法验签，返回bool值
        $result = (bool)openssl_verify($data, $sign, $res);

        //释放资源
        openssl_free_key($res);

        return $result;
    }

     public function decrypt($content)
     {
         //读取商户私钥
         $priKey = file_get_contents($this->local_pk);

         //转换为openssl密钥，必须是没有经过pkcs8转换的私钥
         $res = openssl_get_privatekey($priKey);

         //声明明文字符串变量
         $result = '';

         //循环按照128位解密
         for ($i = 0; $i < strlen($content)/128; $i++) {
             $data = substr($content, $i * 128, 128);

             //拆分开长度为128的字符串片段通过私钥进行解密，返回$decrypt解析后的明文
             openssl_private_decrypt($data, $decrypt, $res);

             //明文片段拼接
             $result .= $decrypt;
         }

         //释放资源
         openssl_free_key($res);

         //返回明文
         return $result;
     }
}
