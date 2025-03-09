<?php

namespace PhpHttpServer\Tests\Core;

use PhpHttpServer\Core\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testConstructor()
    {
        $response = new Response('Test body', 200, ['Header' => 'Value']);

        $this->assertEquals('Test body', $response->getBody());
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(['Header' => 'Value'], $response->getHeaders());
    }

    public function testSetStatusCode()
    {
        $response = new Response();
        $response->setStatusCode(404);

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testSetHeader()
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    }

    public function testSetBody()
    {
        $response = new Response();
        $response->setBody('New Body Content');

        $this->assertEquals('New Body Content', $response->getBody());
    }

    public function testAppendBody()
    {
        $response = new Response('Initial Body');
        $response->appendBody(' appended content');

        $this->assertEquals('Initial Body appended content', $response->getBody());
    }

    public function testSendText()
    {
        $response = new Response();
        $response->sendText('This is a text response');

        $this->assertEquals('This is a text response', $response->getBody());
        $this->assertEquals('text/plain', $response->getHeader('Content-Type'));
    }

    public function testSendJson()
    {
        $response = new Response();
        $response->sendJson(['key' => 'value']);

        $this->assertEquals('{"key":"value"}', $response->getBody());
        $this->assertEquals('application/json', $response->getHeader('Content-Type'));
    }

    public function testSendHtml()
    {
        $response = new Response();
        $response->sendHtml('<h1>HTML Response</h1>');

        $this->assertEquals('<h1>HTML Response</h1>', $response->getBody());
        $this->assertEquals('text/html', $response->getHeader('Content-Type'));
    }

    public function testSendOptions()
    {
        $response = new Response();
        $response->sendOptions(['GET', 'POST']);

        $this->assertEquals('GET, POST', $response->getHeader('Allow'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody());
    }

    public function testSendHead()
    {
        $response = new Response();
        $response->sendHead(128);

        $this->assertEquals(128, $response->getHeader('Content-Length'));
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $response->getBody());
    }

    public function testRender()
    {
        $response = new Response();
        $data = ['name' => 'John'];
        $filePath = __DIR__ . '/test_template.php';

        $response->render($filePath, $data);

        $this->assertStringContainsString('John', $response->getBody());
        $this->assertEquals('text/html', $response->getHeader('Content-Type'));
    }

    public function testRedirect()
    {
        $response = new Response();
        $response->redirect('http://example.com');

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertEquals('http://example.com', $response->getHeader('Location'));
        $this->assertEquals('', $response->getBody());
    }

    public function testSendFile()
    {
        $response = new Response();
        $filePath = __DIR__ . '/testfile.txt';

        // Ensure the file exists before testing
        $this->assertFileExists($filePath);

        $response->sendFile($filePath);

        // Check if the response headers and body are set correctly
        $this->assertEquals(mime_content_type($filePath), $response->getHeader('Content-Type'));
        $this->assertEquals(filesize($filePath), $response->getHeader('Content-Length'));
        $this->assertEquals(file_get_contents($filePath), $response->getBody());
    }

    public function testDownload()
    {
        $response = new Response();
        $filePath = __DIR__ . '/testfile.txt';

        // Ensure the file exists before testing
        $this->assertFileExists($filePath);

        $response->download($filePath, 'downloaded_testfile.txt');

        // Check if the response headers and body are set correctly
        $this->assertEquals('attachment; filename="downloaded_testfile.txt"', $response->getHeader('Content-Disposition'));
        $this->assertEquals(mime_content_type($filePath), $response->getHeader('Content-Type'));
        $this->assertEquals(filesize($filePath), $response->getHeader('Content-Length'));
        $this->assertEquals(file_get_contents($filePath), $response->getBody());
    }

    public function testSetAndAppendHeader()
    {
        $response = new Response();
        $response->setHeader('X-Test-Header', 'TestValue');

        $this->assertEquals('TestValue', $response->getHeader('X-Test-Header'));

        $response->append('X-Test-Header', 'AnotherValue');
        $this->assertEquals('TestValue, AnotherValue', $response->getHeader('X-Test-Header'));
    }

    public function testVary()
    {
        $response = new Response();
        $response->vary('Accept-Encoding');

        $this->assertEquals('Accept-Encoding', $response->getHeader('Vary'));
    }

    public function testIs()
    {
        $response = new Response();
        $response->setHeader('Content-Type', 'application/json');

        $this->assertTrue($response->is('application/json'));
        $this->assertFalse($response->is('text/html'));
    }

    public function testStatus()
    {
        $response = new Response();
        $response->status(404);

        $this->assertEquals(404, $response->getStatusCode());
    }
}