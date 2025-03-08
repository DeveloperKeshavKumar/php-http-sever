<?php

namespace PhpHttpServer\Core;

class Request
{
    private $method;
    private $uri;
    private $protocol;
    private $headers;
    private $body;

    public function __construct($rawRequest)
    {
        $this->parseRequest($rawRequest);
    }

    private function parseRequest($rawRequest)
    {
        // Split the request into lines
        $lines = explode("\r\n", $rawRequest);

        // Parse the request line (e.g., "GET / HTTP/1.1")
        $requestLine = array_shift($lines);
        list($this->method, $this->uri, $this->protocol) = explode(' ', $requestLine);

        // Parse headers
        $this->headers = [];
        while ($line = array_shift($lines)) {
            if (empty($line)) {
                // An empty line indicates the end of headers and the start of the body
                break;
            }
            list($name, $value) = explode(': ', $line, 2);
            $this->headers[$name] = $value;
        }

        // Parse body (if any)
        $this->body = implode("\r\n", $lines);
    }

    // Getters for request properties
    public function getMethod()
    {
        return $this->method;
    }

    public function getUri()
    {
        return $this->uri;
    }

    public function getProtocol()
    {
        return $this->protocol;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getBody()
    {
        return $this->body;
    }

    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }
}