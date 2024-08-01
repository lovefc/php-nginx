![php-nginx](logo.svg)

用php开发的类似nginx的web服务器,可用于学习

中文介绍 | [English](https://github.com/lovefc/php-nginx/blob/master/doc/readme-en.md)

## 基础功能
*  支持windows|linux环境
*  支持域名绑定
*  跟nginx类似的配置文件
*  处理静态文件，索引文件以及目录索引
*  支持HTTPS
*  支持PHP-FPM执行php文件

## 使用环境

PHP >= 7.4.0

并且开启以下扩展：shell_exec,proc_open,popen,fsockopen,pfsockopen,stream_socket_server,mb_convert_encoding

系统会监测环境，并给出提示

## 基础使用

```
php index.php [-c filename]   [ start | restart | stop ] [ -v ] 
```
**\-c** 为 php-nginx 指定一个配置文件，来代替缺省的。

**\-v** 显示 php-nginx 的版本。

> 在linux环境下，可以使用 php index.php -c filename [ start | restart | stop ] 来进行对单一配置的操作
> windows环境下，则不行，重启和停止都是全部重启，全部停止


## 线上测试地址

有趣乎:`https://nginx.fcphp.cn`

这是一个由php-nginx搭建的网站

## 更新记录

2024/07/29:添加了伪静态Rewrite功能，修复了cookies和session失效问题


## 配置信息(目前已支持的语法)
```
server 
{
        #端口号
        listen 80;
		
        #域名
        server_name 127.0.0.1;
		
        #错误跳转
        error_page 404 $path/html/404.html;
		
	#502跳转
	error_page 502 $path/html/50x.html;

        #error_page 502 https://www.baidu.com/;
		
	#SSL证书
	#这里crt或者pem都行
        #ssl_certificate  $path/conf/ssl/server.crt;
        #ssl_certificate_key  $path/conf/ssl/server.key;
		
	#主目录
	root   "$path/html";
		
	#默认索引文件
	index  index.php index.html index.htm;
		
	#是否启动gzip压缩,on代表启动,off代表开启
	gzip  on;
		
	#需要压缩的常见静态资源
	gzip_types text/plain application/javascript application/x-javascript text/css application/xml text/javascript application/x-httpd-php image/jpeg image/png;
		
	#压缩的等级,数字选择范围是1-9,数字越小压缩的速度越快,消耗cpu就越大
	gzip_comp_level 9;
		
	#开启目录浏览功能
        autoindex on;
        
	# 添加header头
        #add_header 'Access-Control-Allow-Origin' '*';
        #add_header 'Access-Control-Allow-Credentials' 'true';  
        #add_header 'Access-Control-Allow-Methods' 'GET,POST,PUT,DELETE,PATCH,OPTIONS';  
        #add_header 'Access-Control-Allow-Headers' 'DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Authorization, token';
		
	#访问日志
	access_log  $path/logs/access.log;
		
	#错误日志
        error_log  $path/logs/error.log;
		
	#js和css文件缓存,可以指定要缓存的后缀文件
	#缓存数可以用 数字+英文表示
	#expires 30s;缓存30秒 
        #expires 30m;缓存30分钟   
        #expires 30h;缓存30小时
        #expires 30d;缓存30天
        #纯数字只代表秒数		
        location ~*\.(js|css)$
        {
            expires 100;
        }	
		
	#禁止访问这些文件，return 状态码或者一个网址
        location ~(\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)
        {
            return http://lovefc.cn;
	}	
		
	#伪静态配置,目前支持的较为简单, 最好固定为这个,能满足基本的要求
	#rewrite ^(.*)$ /index.php?$args;
		
	# 配置php-fpm监听地址，也可以链接远程的fpm监听地址,或者使用"/run/php/php7.4-fpm.sock"
        location ~ \.php(.*)$ {
            fastcgi_pass 127.0.0.1:9000;
        }
		
}
```

> 配置中的$path代表当前目录,如果要配置其它目录,请填写全绝对路径

> 日志和错误日志读写，要确保目录有可读写权限

## 注意事项

* 没有使用epoll模型,不支持很大的并发,请勿在正式环境使用(以后我在考虑上不上,因为要用时间肝)
* 在win下启动,会默认启动cgi,要停止必须使用`php index.php stop`来进行停止
* win下如果启动了php-cgi,如果你有wsl环境并且启动了php-fpm,那么会冲突,导致php执行出错
* 启动并没有限制,如果你启动了两次,那么恭喜你,现在又多了(conf*1)个进程
* 虽然致敬了nginx的配置,但是跟nginx的配置还是略微不同的,不能直接复制,请参考项目中的配置使用

## 鸣谢

感谢以下开源项目为 php-nginx 提供支持

* [PHP-FastCGI-Client](https://github.com/adoy/PHP-FastCGI-Client/) (PHP FastCGI客户端)
* [PHP-CGI-Spawner](https://github.com/deemru/php-cgi-spawner/) (Windows FastCG客户端)

## LICENSE

php-nginx is released under the [MIT license](https://github.com/lovefc/php-nginx/blob/master/LICENSE)
