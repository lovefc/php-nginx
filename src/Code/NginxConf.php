<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-10-21 16:36:41
 * @LastEditTime : 2022-11-16 16:46:11
 */

namespace FC\Code;

class NginxConf
{
    public static $Configs = [];

    /**
     * 要匹配的字符串
     *
     * @var array
     */
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
        "rewrite",
    ];

    /**
     * 包含目录的字符串
     *
     * @var array
     */
    public static $dirs = [
        'error_page',
        'root',
        'ssl_certificate',
        'ssl_certificate_key',
        'access_log',
        'error_log',
    ];

    /**
     * 要匹配的符号
     *
     * @var array
     */
    public static $parameRules = [
        '~*', //为不区分大小写匹配(可用正则表达式)

        '^~', //开头表示uri以某个常规字符串开头

        '!~', //为区分大小写不匹配

        '~', //为区分大小写匹配(可用正则表达式)

        '!~*', //为不区分大小写不匹配

        '/', //通用匹配, 如果没有其它匹配,任何请求都会匹配到。

        '=', // 表示精确匹配
    ];

    /**
     * 获取文件夹内指定后缀的所有文件
     *
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

    /**
     * 整理字符串
     *
     * @param [type] $str
     * @return string
     */
    public static function trimStr($str)
    {
        $str = $str[1];
        $str = str_replace(' ', '', $str);
        return $str;
    }

    /**
     * 解析参数
     *
     * @param [type] $arr
     * @param [type] $confs
     * @return void
     */
    public static function analysis($arr, &$confs)
    {
        foreach ($arr as $text) {
            $text = trim($text);
            $text2 = substr($text, 0, 1);
            foreach (self::$parameters as $k2 => $v2) {
                if ($text2 != '#' && preg_match("/^{$v2}\s+/is", $text)) {
                    $text = trim(substr($text, strlen($v2)));
                    if (in_array($v2, self::$dirs)) {
                        $text = str_replace('$path', PATH, $text);
                    }
                    if ($v2 == 'add_header') {
                        $_arrs = explode(" ", $text);
                        $confs[$v2][] = $_arrs[0] . ":" . trim($_arrs[1]);
                    } else if ($v2 == 'error_page') {
                        $_arrs = explode(" ", $text);
                        $confs[$v2][$_arrs[0]] = trim($_arrs[1]);
                    } else {
                        $arrs = array_values(array_filter(explode(" ", $text)));
                        $confs[$v2] = $arrs;
                    }
                }
            }
        }
    }

    /**
     * 匹配location字符串
     *
     * @param [type] $text
     * @return void
     */
    public static function pregLocation(&$text)
    {
        preg_match_all("/location\s+(.+?)\s*{(.+?)\s*}/is", $text, $matches2);
        $matches2_status = $matches2[1][0] ?? '';
        $locations = [];
        if ($matches2_status) {
            foreach ($matches2[1] as $k => $v) {
                $preg = trim(str_replace(self::$parameRules, "", $v));
                $locations[$preg] = trim($matches2[2][$k]);
            }
        }
        $text = preg_replace('/location\s+(.+?)\s*{(.+?)\s*}/is', '', $text);
        return $locations;
    }

    /**
     * 读取nginx配置文件信息
     *
     * @param string $file 文件地址
     * @return array
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

    /**
     * 读取配置
     *
     * @param string $path 文件地址
     * @return void
     */
    public static function readConf($path = '')
    {
        $conf = self::getConf($path);
        foreach ($conf['server_name'] as $v) {
            if (isset(self::$Configs[$v])) {
                die(Tools::colorFont("{$v}-The domain name is bound, and it is bound repeatedly, Please check the configuration!", "红") . PHP_EOL);
            }
            self::$Configs[$v] = $conf;
        }
    }

    /**
     * 读取所有配置
     *
     * @param string $path 文件地址
     */
    public static function readAllConf($path = '', $extensions = ['conf'])
    {
        if (!is_dir($path)) {
            return false;
        }
        $files = [];
        self::getFiles($files, $path, $extensions);
        foreach ($files as $v) {
            self::readConf($v);
        }
    }
}
