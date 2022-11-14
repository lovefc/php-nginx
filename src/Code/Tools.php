<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-11-14 19:09:00
 */

namespace FC\Code;

class Tools
{
    // 检查环境
    public static function checkEnvironment()
    {
        $funcs = [
            "shell_exec",
            "proc_open",
            "popen",
            "fsockopen",
            "pfsockopen",
            "stream_socket_server",
			"mb_convert_encoding",
        ];
        $status = 0;
        $disEnable = [];
        foreach ($funcs as $v) {
            if (!function_exists($v)) {
                $status = 1;
                $disEnable[] = $v;
            }
        }
        if ($status == 1) {
            $text = implode(",", $disEnable);
            echo self::colorFont("\"{$text}\"--Function is disabled,Please delete this function in the option of disable_functions in php.ini!", '红') . PHP_EOL;
            die();
        }
    }

    // 检查url
    public static function checkUrl($url)
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    // 检查IP
    public static function checkIp($ip)
    {
        if (filter_var($ip, FILTER_VALIDATE_IP) === FALSE) {
            return false;
        } else {
            return true;
        }
    }

    // 生成空格
    public static function spaces($str, $max = 120)
    {
        // iconv('utf-8', 'gb2312', $str);
        $str = mb_convert_encoding($str, "GBK", "UTF-8");
        $len = strlen($str);
        $text = '';
        for ($len; $len <= $max; $len++) {
            $text .= " ";
        }
        return $text;
    }

    // 换算大小
    public static function transfByte($byte)
    {
        $KB = 1024;
        $MB = $KB * 1024;
        $GB = $MB * 1024;
        $TB = $GB * 1024;
        if ($byte < $KB) {
            return $byte . ' B';
        } elseif ($byte < $MB) {
            return round($byte / $KB, 2) . ' KB';
        } elseif ($byte < $GB) {
            return round($byte / $MB, 2) . ' MB';
        } elseif ($byte < $TB) {
            return round($byte / $GB, 2) . ' GB';
        } else {
            return round($byte / $TB, 2) . ' TB';
        }
    }

    // 换算时间
    public static function timeConversion($text)
    {
        if (empty($text)) {
            return 0;
        }
        $str = strtolower(substr($text, -1));
        $time = substr($text, 0, strlen($text) - 1);
        switch ($str) {
            case 's':
                $time = $time;
                break;
            case 'm':
                $time = 60 * $time;
                break;
            case 'h':
                $time = 3600 * $time;
                break;
            case 'd':
                $time = 86400 * $time;
                break;
            default:
                $time =  $text;
        }
        return $time;
    }

    // 显示颜色文字
    public static function colorFont($str, $font = '', $bg = '', $fs = '')
    {
        $c_fs = [
            '粗体' => 1,
            '非粗体' => 22,
            '下划线' => 4,
            '无下划线' => 24,
            '闪烁' => 5,
            '无闪烁' => 25,
            '反显' => 7,
            '无反显' => 27
        ];
        $c_bg = [
            '黑' => 40,
            '红' => 41,
            '绿' => 42,
            '黄' => 43,
            '蓝' => 44,
            '紫' => 45,
            '深绿' => 46,
            '白' => 47,
        ];
        $c_font = [
            '黑' => 30,
            '红' => 31,
            '绿' => 32,
            '黄' => 33,
            '蓝' => 34,
            '紫' => 35,
            '深绿 ' => 36,
            '白' => 37,
            '默认' => 0,
        ];
        $fs = $c_fs[$fs] ?? '';
        $bg = $c_bg[$bg] ?? '';
        $font = $c_font[$font] ?? '';
        return "\033[{$fs};{$bg};{$font}m{$str}\033[0m";
    }

    // 命令行帮助
    public static function cmdHelp()
    {
    }
}
