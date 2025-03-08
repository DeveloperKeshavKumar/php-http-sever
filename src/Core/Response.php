<?php

namespace PhpHttpServer\Core;

class Response
{
    private $statusCode;
    private $headers;
    private $body;

    public function __construct($body = '', $statusCode = 200, $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function sendText($text)
    {
        $this->setHeader('Content-Type', 'text/plain')
            ->setBody($text);
        return $this;
    }

    public function sendJson($data)
    {
        $this->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
        return $this;
    }

    public function sendHtml($html)
    {
        $this->setHeader('Content-Type', 'text/html')
            ->setBody($html);
        return $this;
    }

    public function sendOptions($allowedMethods)
    {
        $this->setHeader('Allow', implode(', ', $allowedMethods))
            ->setStatusCode(200)
            ->setBody('');
        return $this;
    }

    public function sendHead($contentLength = 0)
    {
        $this->setHeader('Content-Length', $contentLength)
            ->setStatusCode(200)
            ->setBody('');
        return $this;
    }

    public function render($file, $data = [])
    {
        // Extract the data into variables
        extract($data);

        // Start output buffering
        ob_start();

        // Include the template file
        include $file;

        // Get the rendered HTML from the buffer
        $html = ob_get_clean();

        // Send the HTML response
        return $this->sendHtml($html);
    }

    public function send($conn)
    {
        // Build the status line
        $statusLine = "HTTP/1.1 {$this->statusCode} " . $this->getStatusText($this->statusCode) . "\r\n";

        // Build the headers
        $headers = '';
        foreach ($this->headers as $name => $value) {
            $headers .= "{$name}: {$value}\r\n";
        }

        // Add Connection: close header
        $headers .= "Connection: close\r\n";

        // Build the response
        $response = $statusLine . $headers . "\r\n" . $this->body;

        // Send the response
        fwrite($conn, $response);
    }

    private function getStatusText($statusCode)
    {
        $statusTexts = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        return $statusTexts[$statusCode] ?? 'Unknown Status';
    }
}