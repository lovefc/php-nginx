### PHP-Nginx

用纯php开发的类似于nginx的软件 (功能不多，慢慢摸索添加中。。。)

****基础功能：****
*  支持windows|linux启动
*  跟nginx一样的配置文件
*   处理静态文件，索引文件以及自动索引；
*   支持HTTPS；
*   支持PHP-FPM

****基础使用：****
```
php php-nginx [-c filename]   [ start | restart | stop ] [ -v ] 
```
**\-c** 为 php-nginx 指定一个配置文件，来代替缺省的。

**\-v** 显示 nginx 的版本。

****配置信息(目前已支持的语法)：****
```
server 
{
        #端口号
        listen  443;
		# 域名
        server_name php-nginx.com;
		# 错误跳转
        #error_page 404/404.html;
		#SSL证书
        ssl_certificate  /home/wwwroot/php-static/conf/ssl/server.crt;
        ssl_certificate_key  /home/wwwroot/php-static/conf/ssl/server.key;
		# 主目录
		root   "/home/wwwroot/php-static/conf";
		# 默认索引文件
		index  index.html index.htm;
		#是否启动gzip压缩,on代表启动,off代表开启
		gzip  on;
		#需要压缩的常见静态资源
		gzip_types text/plain application/javascript application/x-javascript text/css application/xml text/javascript application/x-httpd-php image/jpeg image/png;
		#压缩的等级,数字选择范围是1-9,数字越小压缩的速度越快,消耗cpu就越大
		gzip_comp_level 4;
		#开启目录浏览功能
        autoindex on;
		#关闭详细文件大小统计，让文件大小显示MB，GB单位，默认为b
        #autoindex_exact_size off; 
        #开启以服务器本地时区显示文件修改日期	
        #autoindex_localtime on;              		
}
```
> 配置需要注意的问题
> 1.端口号1个配置只支持绑定一个



