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

**\-v**显示 nginx 的版本。










