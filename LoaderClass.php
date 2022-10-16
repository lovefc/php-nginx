<?php

/*
 * 基于psr4规范的加载类
 * @Author: lovefc 
 * @Date: 2016/8/29 10:51:27 
 * @Last Modified by: lovefc
 * @Last Modified time: 2019-09-16 14:57:38
 */

class LoaderClass
{

    // 一个关联数组，是一个命名空间前缀和值
    public static $prefixes = [];


    // 要加载的类文件后缀,是一个数组
    public static $filext = [];

    // 默认文件后缀
    public static $defaultfilext = '.php';

    // 自动装载函数
    public static function register()
    {
        spl_autoload_register(array('self', 'loadClass'));
    }

    //添加类文件或者函数文件
    public static function AddFile($config)
    {
        if (isset($config['psr-4']) && is_array($config['psr-4'])) {
            foreach ($config['psr-4'] as $key => $value) {
                if (is_array($value)) {
                    $base_dir = isset($value[0]) ? $value[0] : null;
                    $filext = isset($value[1]) ? $value[1] : null;
                    $prepend = isset($value[2]) ? $value[2] : null;
                    if ($base_dir) {
                        self::AddPsr4($key, $base_dir, $filext, $prepend);
                    }
                } else {
                    self::AddPsr4($key, $value);
                }
            }
        }
        if (isset($config['files']) && is_array($config['files'])) {
            foreach ($config['files'] as $value) {
                self::requireFile($value);
            }
        }
    }
	
    /*
     * psr0的增加，要求必须是路径最后的字符串等于命名空间名,其实是psr-4的简写
     * @param $base_dir 路径名
     * @param $filext 文件后缀
     * @param $prepend 优先级
     */

    public static function AddPsr0($base_dir, $filext = null, $prepend = false)
    {
        $base_dirs = rtrim($base_dir, '/');
        $base_dir = $base_dirs . '/';
        $dirs = explode('/', $base_dirs);
        $prefix = end($dirs);
        //die($prefix.'<br />'.$base_dir);
        if (isset(self::$prefixes[$prefix]) === false) {
            self::$prefixes[$prefix] = array();
        }
        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $base_dir);
        } else {
            array_push(self::$prefixes[$prefix], $base_dir);
        }
        if (empty($filext)) {
            self::$filext[$prefix] = $filext;
        }
    }
	
    /*
     * $prefix 命名空间名
     * @param $base_dir 地址路径
     * @param $filext 文件后缀
     * @param $prepend 优先级
     */

    public static function AddPsr4($prefix, $base_dir, $filext = null, $prepend = false)
    {
        $prefix = trim($prefix, '\\');
        $base_dir = strtr($base_dir, '\\', '/');
        // 检测是否存在
        if(!file_exists($base_dir)){
            return false;
        }
        // 如果不是文件
        if(!is_file($base_dir)){
            $base_dir = rtrim($base_dir, '/') . '/';
        }
        if (isset(self::$prefixes[$prefix]) === false) {
            self::$prefixes[$prefix] = array();
        }
        if ($prepend) {
            array_unshift(self::$prefixes[$prefix], $base_dir);
        } else {
            array_push(self::$prefixes[$prefix], $base_dir);
        }
        if (empty($filext)) {
            self::$filext[$prefix] = $filext;
        }
    }

    /*
     * 如果文件存在，那么就加载它哦！
     * @return void
     */

    public static function requireFile($file)
    {
        if (is_file($file)) {
            require_once($file);
            return true;
        }
        return false;
    }

    /*
     * 解析命名
     * @param $class 类名
     */

    public static function loadClass($class)
    {
        $prefix = $class;
        $filext = isset(self::$filext[$prefix]) ? self::$filext[$prefix] : self::$defaultfilext;
        // 获取当前框架主目录
        $dir_paths = dirname(strtr(dirname(__DIR__), '\\', '/'));
        // 模拟psr0加载
        $dir_file = $dir_paths . '/' . str_replace("\\", "/", $prefix) . $filext;
        if (self::requireFile($dir_file)) {
            return $dir_file;
        }
        // psr4加载
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos);
            $prefix2 = substr($class, $pos + 1);
            if (array_key_exists($prefix, self::$prefixes)) {
                foreach (self::$prefixes[$prefix] as $value) {
                    $dir_path = isset($value) ? $value : null;
                    if ($dir_path) {
                        $filext = isset(self::$filext[$prefix]) ? self::$filext[$prefix] : self::$defaultfilext;
                        $dir_file = str_replace("\\", "/", $dir_path . $prefix2 . $filext);
                        if (self::requireFile($dir_file)) {
                            return $dir_file;
                        }
                    }
                }
            }
            $prefix = rtrim($prefix, '\\');
        }
        // 没有找到存在的文件
        return false;
    }
}
