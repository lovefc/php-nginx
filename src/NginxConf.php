<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-10-21 16:36:41
 * @LastEditTime : 2022-11-01 22:14:03
 */

namespace FC;

class NginxConf
{
    public static $Configs = [];
    public static $parameters = [
      "autoindex",
      "autoindex_exact_size",
      "autoindex_localtime",	
      "add_header",
      "listen",
      "server_name",
      "root",
      "index",
      "error_page",
      "ssl_certificate",
      "ssl_certificate_key",
      "access_log",
      "error_log",
      "gzip",
      "gzip_types",
      "gzip_comp_level",
    ];

    /*
= : 严格匹配。如果这个查询匹配，那么将停止搜索并立即处理此请求。
~ : 为区分大小写匹配(可用正则表达式)
!~ : 为区分大小写不匹配
~* : 为不区分大小写匹配(可用正则表达式)
!~* : 为不区分大小写不匹配
^~ : 如果把这个前缀用于一个常规字符串,那么告诉nginx 如果路径匹配那么不测试正则表达式。
    */
    public static $parameRules = [
        '~*',//为不区分大小写匹配(可用正则表达式)

        '^~',//开头表示uri以某个常规字符串开头

        '!~',//为区分大小写不匹配

        '~',//为区分大小写匹配(可用正则表达式)

        '!~*',//为不区分大小写不匹配

        '/',//通用匹配, 如果没有其它匹配,任何请求都会匹配到。

        '=', // 表示精确匹配
    ];

    public static function defaultConf()
    {
        $dir = dirname(__DIR__);
        $conf = [
            '127.0.0.1' => [
                'listen' => [80],
                'server_name' => ['127.0.0.1'],
                'root' => [$dir.DIRECTORY_SEPARATOR.'html'],
                'index' => ['index.html','index.htm'],
                'autoindex' => ['on']
            ]
        ];
		$conf = [];
        return $conf;
    }

    /**
     * 获取文件夹内指定后缀的所有文件
     * @param array $result 结果集
     * @param string $dir 指定目录
     * @param array $filter 后缀过滤，为空即全部文件
     */
    public static function getFiles(&$result, $dir, $filter = [])
    {
        $files = array_diff(scandir($dir), array('.', '..', '__MACOSX'));
        if (is_array($files)) {
            foreach ($files as $value) {
                if (is_dir($dir . '/' . $value)) {
                    self::getFiles($result, $dir . '/' . $value, $filter);
                } else {
                    $path_info = pathinfo($dir . '/' . $value);
                    $extension = array_key_exists('extension', $path_info) ? $path_info['extension'] : '';
                    if (empty($filter) || (!empty($filter) && in_array($extension, $filter))) {
                        $result[] = $dir . '/' . $value;
                    }
                }
            }
        }
    }

    // 整理字符串
    public static function trimStr($str)
    {
        $str = $str[1];
        $str = str_replace(' ', '', $str);
        return $str;
    }

    // 解析参数
    public static function analysis($arr, &$confs)
    {
        foreach ($arr as $text) {
            $text = trim($text);
            $text2 = substr($text, 0, 1);
            foreach (self::$parameters  as $v2) {
                if ($text2!='#' && preg_match("/^{$v2}\s+/is", $text)) {
                    if ($v2=='add_header') {
                        $text = trim(substr($text, strlen($v2)));
                        $_arrs = explode(" ", $text);
                        $confs[$v2][$_arrs[0]] = $_arrs[1];
                    } else {
                        if ($v2=='root') {
                            $text = preg_replace("/\"/i", "", $text);
                        }
                        $arrs = array_values(array_filter(explode(" ", trim(substr($text, strlen($v2))))));
                        $confs[$v2] = $arrs;
                    }
                }
            }
        }
    }


    // 匹配location字符串
    public static function pregLocation(&$text)
    {
        preg_match_all("/location\s+(.+?)\s*{(.+?)\s*}/is", $text, $matches2);
        $matches2_status = $matches2[1][0] ?? '';
        $locations = [];																																												
        if ($matches2_status) {
            foreach ($matches2[1] as $k=>$v) {
                $preg = trim(str_replace(self::$parameRules, "", $v));
                $locations[$preg] = trim($matches2[2][$k]);
            }
        }
		$text = preg_replace('/location\s+(.+?)\s*{(.+?)\s*}/is','',$text);
        return $locations;
    }

    /**
     * 读取nginx配置文件信息
     * @param string $file 文件名称
     */
    public static function getConf($file)
    {
        $text = file_get_contents($file);
        $matches = [];
        preg_match_all('/server\s*{(.*)\s*}/is', $text, $matches);
        $text = $matches[1][0];
        // 去掉注释
        $text = preg_replace("/\#(.*)\s+/i", "", $text);
        // 去掉空格和'字符串
        $text = preg_replace_callback("/'(.+?)'/i", 'self::trimStr', $text);
        $text = preg_replace_callback('/"(.+?)"/i', 'self::trimStr', $text);		
        // 匹配location字符串
        $confs = [];
        $confs['location'] = self::pregLocation($text);
        $arr = explode(";", $text);
        self::analysis($arr, $confs);
        return $confs;
    }
    
    // 读取配置
    public static function readConf($path='')
    {
        $conf = self::getConf($path);
        foreach ($conf['server_name'] as $v) {
            if (isset(self::$Configs[$v])) {
               die(Tools::colorFont("{$v}-The domain name is bound, and it is bound repeatedly, Please check the configuration!", "红"));
            }
            self::$Configs[$v] = $conf;
        }
    }
	
    // 读取所有配置
    public static function readAllConf($path='', $extensions=['conf'])
    {
        if (!is_dir($path)) {
            return false;
        }
        $files = [];
        self::getFiles($files, $path, $extensions);
        foreach ($files as $v) {
           self::readConf($v);
        }
        $conf2 = self::defaultConf();
        self::$Configs = array_merge($conf2, self::$Configs);
    }
}
