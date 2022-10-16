<?php

namespace FC;

/*
 * 常用http协议状态
 * @Author: lovefc 
 * @Date: 2021-12-02 10:05:57 
 * @Date         : 2021-12-02 10:05:57
 * @LastEditTime : 2022-08-17 02:55:28
 */

class HttpCode
{

   // http状态码
   public static $STATUS_CODES = [
      100 => '100 Continue', //继续。客户端应继续其请求
      101 => '101 Switching Protocols', //切换协议。服务器根据客户端的请求切换协议。只能切换到更高级的协议，例如，切换到HTTP的新版本协议
      200 => '200 OK', //请求成功。一般用于GET与POST请求
      201 => '201 Created', //已创建。成功请求并创建了新的资源
      202 => '202 Accepted', //已接受。已经接受请求，但未处理完成
      203 => '203 Non-Authoritative Information', //非授权信息。请求成功。但返回的meta信息不在原始的服务器，而是一个副本
      204 => '204 No Content', //无内容。服务器成功处理，但未返回内容。在未更新网页的情况下，可确保浏览器继续显示当前文档
      205 => '205 Reset Content', //重置内容。服务器处理成功，用户终端（例如：浏览器）应重置文档视图。可通过此返回码清除浏览器的表单域
      206 => '206 Partial Content', //部分内容。服务器成功处理了部分GET请求
      300 => '300 Multiple Choices', //多种选择。请求的资源可包括多个位置，相应可返回一个资源特征与地址的列表用于用户终端（例如：浏览器）选择
      301 => '301 Moved Permanently', //永久移动。请求的资源已被永久的移动到新URI，返回信息会包括新的URI，浏览器会自动定向到新URI。今后任何新的请求都应使用新的URI代替
      302 => '302 Found', //临时移动。与301类似。但资源只是临时被移动。客户端应继续使用原有URI
      303 => '303 See Other', //查看其它地址。与301类似。使用GET和POST请求查看
      304 => '304 Not Modified', //未修改。所请求的资源未修改，服务器返回此状态码时，不会返回任何资源。客户端通常会缓存访问过的资源，通过提供一个头信息指出客户端希望只返回在指定日期之后修改的资源
      305 => '305 Use Proxy', //使用代理。所请求的资源必须通过代理访问
      306 => '306 Unused', //已经被废弃的HTTP状态码
      307 => '307 Temporary Redirect', //临时重定向。与302类似。使用GET请求重定向
      400 => '400 Bad Request', //客户端请求的语法错误，服务器无法理解
      401 => '401 Unauthorized', //请求要求用户的身份认证
      402 => '402 Payment Required', //保留，将来使用
      403 => '403 Forbidden', //服务器理解请求客户端的请求，但是拒绝执行此请求
      404 => '404 Not Found', //服务器无法根据客户端的请求找到资源（网页）。通过此代码，网站设计人员可设置"您所请求的资源无法找到"的个性页面
      405 => '405 Method Not Allowed', //客户端请求中的方法被禁止
      406 => '406 Not Acceptable', //服务器无法根据客户端请求的内容特性完成请求
      407 => '407 Proxy Authentication Required', //请求要求代理的身份认证，与401类似，但请求者应当使用代理进行授权
      408 => '408 Request Time-out', //服务器等待客户端发送的请求时间过长，超时
      409 => '409 Conflict', //服务器完成客户端的 PUT 请求时可能返回此代码，服务器处理请求时发生了冲突
      410 => '410 Gone', //客户端请求的资源已经不存在。410不同于404，如果资源以前有现在被永久删除了可使用410代码，网站设计人员可通过301代码指定资源的新位置
      411 => '411 Length Required', //服务器无法处理客户端发送的不带Content-Length的请求信息
      412 => '412 Precondition Failed', //客户端请求信息的先决条件错误
      413 => '413 Request Entity Too Large', //由于请求的实体过大，服务器无法处理，因此拒绝请求。为防止客户端的连续请求，服务器可能会关闭连接。如果只是服务器暂时无法处理，则会包含一个Retry-After的响应信息
      414 => '414 Request-URI Too Large', //请求的URI过长（URI通常为网址），服务器无法处理
      415 => '415 Unsupported Media Type', //服务器无法处理请求附带的媒体格式
      416 => '416 Requested range not satisfiable', //客户端请求的范围无效
      417 => '417 Expectation Failed', //服务器无法满足Expect的请求头信息
      500 => '500 Internal Server Error', //服务器内部错误，无法完成请求
      501 => '501 Not Implemented', //服务器不支持请求的功能，无法完成请求
      502 => '502 Bad Gateway', //作为网关或者代理工作的服务器尝试执行请求时，从远程服务器接收到了一个无效的响应
      503 => '503 Service Unavailable', //由于超载或系统维护，服务器暂时的无法处理客户端的请求。延时的长度可包含在服务器的Retry-After头信息中
      504 => '504 Gateway Time-out', //充当网关或代理的服务器，未及时从远端服务器获取请求
      505 => '505 HTTP Version not supported' //不支持请求的HTTP协议的版本，无法完成处理
   ];

   // http请求方法
   public static $METHODS = [
      'GET',   //请求指定的页面信息，并返回实体主体。
      'HEAD',   //类似于 GET 请求，只不过返回的响应中没有具体的内容，用于获取报头
      'POST',   //向指定资源提交数据进行处理请求（例如提交表单或者上传文件）。数据被包含在请求体中。POST 请求可能会导致新的资源的建立和/或已有资源的修改。
      'PUT',   //从客户端向服务器传送的数据取代指定的文档的内容。
      'DELETE',   //请求服务器删除指定的页面。
      'CONNECT',   //HTTP/1.1 协议中预留给能够将连接改为管道方式的代理服务器。
      'OPTIONS',   //允许客户端查看服务器的性能。
      'TRACE',   //回显服务器收到的请求，主要用于测试或诊断。
      'PATCH',   //是对 PUT 方法的补充，用来对已知资源进行局部更新
   ];

   // http的head头
   public static $HEADER = [
      'Allow',
      //服务器支持哪些请求方法（如GET、POST等）。
	  
      'Connection',
	  
      'Content-Encoding', //文档的编码（Encode）方法。只有在解码之后才可以得到Content-Type头指定的内容类型。利用gzip压缩文档能够显著地减少HTML文档的下载时间。Java的GZIPOutputStream可以很方便地进行gzip压缩，但只有Unix上的Netscape和Windows上的IE 4、IE 5才支持它。因此，Servlet应该通过查看Accept-Encoding头（即request.getHeader("Accept-Encoding")）检查浏览器是否支持gzip，为支持gzip的浏览器返回经gzip压缩的HTML页面，为其他浏览器返回普通页面。

      'Content-Length',
      //表示内容长度。只有当浏览器使用持久HTTP连接时才需要这个数据。如果你想要利用持久连接的优势，可以把输出文档写入 ByteArrayOutputStream，完成后查看其大小，然后把该值放入Content-Length头，最后通过byteArrayStream.writeTo(response.getOutputStream()发送内容。

      'Content-Type', //表示后面的文档属于什么MIME类型。Servlet默认为text/plain，但通常需要显式地指定为text/html。由于经常要设置Content-Type，因此HttpServletResponse提供了一个专用的方法setContentType。
      'Date',
      //当前的GMT时间。你可以用setDateHeader来设置这个头以避免转换时间格式的麻烦。

      'Expires',
      //应该在什么时候认为文档已经过期，从而不再缓存它

      'Last-Modified',
      //文档的最后改动时间。客户可以通过If-Modified-Since请求头提供一个日期，该请求将被视为一个条件GET，只有改动时间迟于指定时间的文档才会返回，否则返回一个304（Not Modified）状态。Last-Modified也可用setDateHeader方法来设置。

      'Location',
      //表示客户应当到哪里去提取文档。Location通常不是直接设置的，而是通过HttpServletResponse的sendRedirect方法，该方法同时设置状态代码为302。

      'Refresh',
      //表示浏览器应该在多少时间之后刷新文档，以秒计。除了刷新当前文档之外，你还可以通过setHeader("Refresh", "5; URL=http://host/path")让浏览器读取指定的页面。注意这种功能通常是通过设置HTML页面HEAD区的＜META HTTP-EQUIV="Refresh" CONTENT="5;URL=http://host/path"＞实现，这是因为，自动刷新或重定向对于那些不能使用CGI或Servlet的HTML编写者十分重要。但是，对于Servlet来说，直接设置Refresh头更加方便。注意Refresh的意义是"N秒之后刷新本页面或访问指定页面"，而不是"每隔N秒刷新本页面或访问指定页面"。因此，连续刷新要求每次都发送一个Refresh头，而发送204状态代码则可以阻止浏览器继续刷新，不管是使用Refresh头还是＜META HTTP-EQUIV="Refresh" ...＞。注意Refresh头不属于HTTP 1.1正式规范的一部分，而是一个扩展，但Netscape和IE都支持它。

      'Server',
      //服务器名字。Servlet一般不设置这个值，而是由Web服务器自己设置。

      'Set-Cookie',
      //设置和页面关联的Cookie。Servlet不应使用response.setHeader("Set-Cookie", ...)，而是应使用HttpServletResponse提供的专用方法addCookie。参见下文有关Cookie设置的讨论。

      'WWW-Authenticate',
      //客户应该在Authorization头中提供什么类型的授权信息？在包含401（Unauthorized）状态行的应答中这个头是必需的。例如，response.setHeader("WWW-Authenticate", "BASIC realm=＼"executives＼"")。注意Servlet一般不进行这方面的处理，而是让Web服务器的专门机制来控制受密码保护页面的访问（例如.htaccess）。 。
      
	  'Access-Control-Allow-Origin',
	  // 跨域
   
   ];
}
