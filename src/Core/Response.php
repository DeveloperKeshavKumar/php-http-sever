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

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function setBody($body)
    {
        $this->body = $body;
        return $this;
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

        // Add Connection: keep-alive header
        $headers .= "Connection: keep-alive\r\n";

        // Build the response
        $response = $statusLine . $headers . "\r\n" . $this->body;

        // Send the response
        fwrite($conn, $response);
    }

    private function getStatusText($statusCode)
    {
        $statusTexts = [
            200 => 'OK',
            404 => 'Not Found',
            500 => 'Internal Server Error',
        ];

        return $statusTexts[$statusCode] ?? 'Unknown Status';
    }
}