<?php

$canshu = [
 "listen",
 "server_name",
 "root",
 "index",
 "error_page",
 "ssl_certificate",
 "ssl_certificate_key",  
 "access_log",
 "error_log",
];


/*
$text = 'root"D:/phpstudy_pro/lv/www/public"';
if(stristr($text, 'root')){
	echo substr($text,strlen('root'));
}

echo strripos($text, 'root').PHP_EOL;  

echo substr($text,strripos($text, 'root'));// 自 PHP 5.3.0 起，输出 US
*/

// 读取所有配置文件
function getConfFile(){
	
}


$file = __DIR__.'/lv.cf_80.conf';
$text = file_get_contents($file);
$matches = [];
preg_match_all('/server\s*{(.*)\s*}/is',$text,$matches);
$text = $matches[1][0];
// 去掉注释
$text = preg_replace("/\#(.*)\s+/i", "", $text); 
$arr = explode(";",$text);
$strs = [];
foreach($arr as $text){
	$text = trim($text);
	$text2 = substr($text,0,1);
	foreach($canshu  as $v2){
		if($text2!='#' && preg_match("/{$v2}\s+/is", $text)){
			$arrs = array_filter(explode(" ",trim(substr($text,strlen($v2)))));
			if($v2!='index') sort($arrs);
			$strs[$v2] = $arrs;
		}
	}
}

print_r($strs);