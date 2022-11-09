<?php
/*
 * @Author       : lovefc
 * @Date         : 2022-09-03 02:11:36
 * @LastEditTime : 2022-11-09 16:06:46
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
            "system",
            "fsockopen",
            "pfsockopen",
            "stream_socket_server",
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
            echo self::colorFont("\"{$text}\"--Function is disabled,Please delete this function in the option of disable_functions in php.ini!", '红').PHP_EOL;
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
