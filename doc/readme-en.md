### PHP-Nginx

Nginx developed with php

[Chinese](https://github.com/lovefc/php-nginx/blob/master/README.md) | English

****Basic functions:****
*  Support windows|linux environment
*  Supporting addon domain
*  Configuration files similar to nginx
*  Handle static files, index files and directory indexes
*  HTTPS support
*  Support php-FPM to execute PHP files

****Basic use：****
```
php index.php [-c filename]   [ start | restart | stop ] [ -v ] 
```
**\-c** Specify a configuration file for php-nginx instead of the default.

**\-v** Show the version of php-nginx.

> Under linux environment, you can use PHP index.php-c filename [start | restart | stop] to operate on a single configuration.
> Under the windows environment, it won't work. Restart and stop are all restarts and all stops.

****Configuration information：****
```
server 
{
        #Port number, multiple supported, separated by spaces
        listen  80 1993;
		
	# Domain name, support multiple, separated by spaces
        server_name 127.0.0.1;
		
	# Wrong jump
        error_page 404 $path/html/404.html;
		
	# 502 jump
	error_page 502 $path/html/404.html;
        #error_page 502 https://www.baidu.com/;
		
	#SSL certificate
        #ssl_certificate  $path/conf/ssl/server.crt;
        #ssl_certificate_key  $path/conf/ssl/server.key;
		
	# Home directory
        root   $path/html;
		
	# Default index file, the first priority match
	index  index.php index.html index.htm;
		
	#Whether to start gzip compression, on means to start, off means to start.
	gzip  on;
		
	#Common static resources that need to be compressed
	gzip_types text/plain application/javascript application/x-javascript text/css application/xml text/javascript application/x-httpd-php;
		
	#Compression level, the number selection range is 1-9, the smaller the number, the faster the compression speed, and the greater the cpu consumption.
	gzip_comp_level 4;
		
	#Turn on the directory browsing function, that is, display directory files without index files.
        autoindex on;
        
	#Add the header, which is used for cross-domain.
        #add_header 'Access-Control-Allow-Origin' '*';
        #add_header 'Access-Control-Allow-Credentials' 'true';  
        #add_header 'Access-Control-Allow-Methods' 'GET,POST,PUT,DELETE,PATCH,OPTIONS';  
        #add_header 'Access-Control-Allow-Headers' 'DNT, X-Mx-ReqToken, Keep-Alive, User-Agent, X-Requested-With, If-Modified-Since, Cache-Control, Content-Type, Authorization, token';
		
	#Access log
	access_log  $path/logs/access2.log;
		
	#Error_log
        error_log  $path/logs/error2.log;
		
	#File cache, you can specify the suffix file to cache.
	#The number of caches can be represented by numbers+English.
	#expires 30s;Cache for 30 seconds
        #expires 30m;Cache for 30 minutes   
        #expires 30h;Cache for 30 hours
        #expires 30d;Cache for 30 days
        #Pure numbers only represent seconds
	
        location ~*\.(js|css|png|jpg|gif|mp4)$
	{
            expires 2h;
        }	
		
	#Do not access these questions, return status code or a URL
        location ~(\.user.ini|\.htaccess|\.git|\.svn|\.project|LICENSE|README.md)
	{
            return http://lovefc.cn;
	}	
		
	#Configure the php-fpm listening address, or link the remote fpm listening address.
        location ~ \.php(.*)$ {
            fastcgi_pass 127.0.0.1:9000;
        }          		
}
```

> The configured $path represents the current directory. If you want to configure other directories, please fill in the absolute path.

> And to ensure that the directory has read-write permission, the log file may not be created in the directory where root belongs.



