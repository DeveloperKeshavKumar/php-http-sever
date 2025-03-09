<?php

namespace PhpHttpServer\Tests\Core;

use PhpHttpServer\Core\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    public function testRequestParsing()
    {
        $rawRequest = "GET /index.html HTTP/1.1\r\nHost: example.com\r\nCookie: name=value\r\n\r\nbody";
        $request = new Request($rawRequest);

        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/index.html', $request->getUri());
        $this->assertEquals('HTTP/1.1', $request->getProtocol());
        $this->assertEquals('example.com', $request->getHeader('Host'));
        $this->assertEquals('value', $request->getCookie('name'));
        $this->assertEquals('body', $request->getBody());
    }

    public function testQueryParams()
    {
        $rawRequest = "GET /index.html?param1=value1&param2=value2 HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);

        $this->assertEquals('value1', $request->getQueryParam('param1'));
        $this->assertEquals('value2', $request->getQueryParam('param2'));
    }

    public function testPathParams()
    {
        $rawRequest = "GET /users/123 HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $request->extractPathParams('~^/users/(?P<id>\d+)$~'); // Use named capture group

        $this->assertEquals(['id' => '123'], $request->getPathParams()); // Expect associative array
    }

    public function testGetMethod()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->isGet());
        $this->assertFalse($request->isPost());
        $this->assertFalse($request->isPut());
        $this->assertFalse($request->isDelete());
    }

    public function testPostMethod()
    {
        $rawRequest = "POST /submit HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->isPost());
        $this->assertFalse($request->isGet());
    }

    public function testPutMethod()
    {
        $rawRequest = "PUT /update HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->isPut());
        $this->assertFalse($request->isGet());
    }

    public function testDeleteMethod()
    {
        $rawRequest = "DELETE /delete HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->isDelete());
        $this->assertFalse($request->isGet());
    }

    public function testGetHeader()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\nConnection: keep-alive\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertEquals('example.com', $request->getHeader('Host'));
        $this->assertEquals('keep-alive', $request->getHeader('Connection'));
        $this->assertNull($request->getHeader('Non-Existing-Header'));
    }

    public function testSetHeader()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $request->setHeader('User-Agent', 'TestAgent');
        $this->assertEquals('TestAgent', $request->getHeader('User-Agent'));
    }

    public function testIs()
    {
        $rawRequest = "POST /upload HTTP/1.1\r\nHost: example.com\r\nContent-Type: multipart/form-data; boundary=----WebKitFormBoundary\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->is('multipart/form-data'));
        $this->assertFalse($request->is('application/json'));
    }

    public function testAccepts()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\nAccept: application/json\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->accepts('application/json'));
        $this->assertFalse($request->accepts('text/html'));
    }

    public function testIsHttps()
    {
        // Simulate HTTP
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertFalse($request->isHttps());

        // Simulate HTTPS
        $_SERVER['HTTPS'] = 'on';
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertTrue($request->isHttps());

        // Clean up
        unset($_SERVER['HTTPS']);
    }

    public function testGetFiles()
    {
        $rawRequest = "POST /upload HTTP/1.1\r\nHost: example.com\r\nContent-Type: multipart/form-data; boundary=----WebKitFormBoundary\r\n\r\n";
        $rawRequest .= "------WebKitFormBoundary\r\nContent-Disposition: form-data; name=\"file\"; filename=\"example.txt\"\r\n\r\nTest content\r\n------WebKitFormBoundary--\r\n";
        $request = new Request($rawRequest);
        $files = $request->getFiles();

        $this->assertCount(1, $files);
        $this->assertEquals('example.txt', $files['file']['filename']);
        $this->assertEquals('Test content', $files['file']['content']);
    }

    public function testGetCookies()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\nCookie: name=value; session=12345\r\n\r\n";
        $request = new Request($rawRequest);
        $cookies = $request->getCookies();
        $this->assertArrayHasKey('name', $cookies);
        $this->assertEquals('value', $cookies['name']);
        $this->assertArrayHasKey('session', $cookies);
        $this->assertEquals('12345', $cookies['session']);
    }

    public function testGetIp()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $request = new Request($rawRequest);
        $this->assertEquals('192.168.1.1', $request->getIp());
    }

    public function testGetHostname()
    {
        $rawRequest = "GET / HTTP/1.1\r\nHost: example.com\r\n\r\n";
        $request = new Request($rawRequest);
        $this->assertEquals('example.com', $request->getHostname());
    }
}