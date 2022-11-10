### PHP-Nginx

用纯php开发的类似于nginx的软件 (功能不多，慢慢摸索添加中。。。)

[English](https://github.com/lovefc/php-nginx/doc/readme-en.md) | 中文介绍

****基础功能：****
*  支持windows|linux环境
*  支持域名绑定
*  跟nginx类似的配置文件
*  处理静态文件，索引文件以及目录索引
*  支持HTTPS
*  支持PHP-FPM执行php文件

****基础使用：****
```
php index.php [-c filename]   [ start | restart | stop ] [ -v ] 
```
**\-c** 为 php-nginx 指定一个配置文件，来代替缺省的。

**\-v** 显示 nginx 的版本。

> 在linux环境下，可以使用 php index.php -c filename [ start | restart | stop ] 来进行对单一配置的操作
> windows环境下，则不行，重启和停止都是全部重启，全部停止

****配置信息(目前已支持的语法)：****
```
server 
{
        #端口号，支持多个，空格隔开
        listen  80 1993;
		
	# 域名,支持多个,空格隔开
        server_name 127.0.0.1;
		
	# 错误跳转
        error_page 404 $path/html/404.html;
		
	# 502跳转
	error_page 502 $path/html/404.html;
        #error_page 502 https://www.baidu.com/;
		
	#SSL证书
        #ssl_certificate  $path/conf/ssl/server.crt;
        #ssl_certificate_key  $path/conf/ssl/server.key;
		
	# 主目录
        root   $path/html;
		
	# 默认索引文件，最前面的优先匹配
	index  index.php index.html index.htm;
		
	#是否启动gzip压缩,on代表启动,off代表开启
	gzip  on;
		
	#需要压缩的常见静态资源
	gzip_types text/plain application/javascript application/x-javascript text/css application/xml text/javascript application/x-httpd-php;
		
	#压缩的等级,数字选择范围是1-9,数字越小压缩的速度越快,消耗cpu就越大
	gzip_comp_level 4;
		
	#开启目录浏览功能,就是在没有索引文件的情况下，显示目录文件情况
        autoindex on;
        
	# 添加header头，这个案例文件头是用于跨域的
        #add_header 'Access-Control-Allow-Origin' '*';
        #add_header 'Access-Control-Allow-Credentials' 'true';  
        #add_header 'Access-Control-Allow-Methods' 'GET,POST,PUT,DELETE,PATCH,OPTIONS';  
        #add_header 'Access-Control-Allow-Headers' 'DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Authorization, token';
		
	#访问日志
	access_log  $path/logs/access2.log;
		
	#错误日志
        error_log  $path/logs/error2.log;
		
	#js和css文件缓存,可以指定要缓存的后缀文件
	#缓存数可以用 数字+英文表示
	#expires 30s;缓存30秒 
        #expires 30m;缓存30分钟   
        #expires 30h;缓存30小时
        #expires 30d;缓存30天
        #纯数字只代表秒数
	
        location ~*\.(js|css|png|jpg|gif|mp4)$
	{
            expires 2h;
        }	
		
	#禁止访问这些问题，return 状态码或者一个网址
        location ~(\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)
	{
            return http://lovefc.cn;
	}	
		
	#配置php-fpm监听地址，也可以链接远程的fpm监听地址
        location ~ \.php(.*)$ {
            fastcgi_pass 127.0.0.1:9000;
        }          		
}
```

> 配置中的$path代表当前目录,如果要配置其它目录,请填写全绝对路径

> 日志和错误日志读写，要确保目录有可读写权限，在root所属的目录，可能无法创建日志文件



