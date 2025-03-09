<?php

namespace PhpHttpServer\Core;

class Request
{
    private $method;
    private $uri;
    private $protocol;
    private $headers;
    private $body;
    private $files = [];
    private $queryParams = [];
    private $pathParams = [];
    private $cookies = [];
    private $hostname;
    private $ip;

    /**
     * Constructor to initialize the request with raw request data.
     *
     * @param string $rawRequest The raw HTTP request string.
     */
    public function __construct($rawRequest)
    {
        $this->parseRequest($rawRequest);
    }

    /**
     * Parses the raw HTTP request into its components.
     *
     * @param string $rawRequest The raw HTTP request string.
     */
    private function parseRequest($rawRequest)
    {
        $lines = explode("\r\n", $rawRequest);

        // Parse the request line
        $requestLine = array_shift($lines);
        list($this->method, $this->uri, $this->protocol) = explode(' ', $requestLine);

        // Parse headers
        $this->headers = [];
        while ($line = array_shift($lines)) {
            if (empty($line)) {
                break; // End of headers
            }
            list($name, $value) = explode(': ', $line, 2);
            $this->headers[$name] = $value;
        }

        // Parse query parameters
        $urlParts = parse_url($this->uri);
        if (isset($urlParts['query'])) {
            parse_str($urlParts['query'], $this->queryParams);
        }

        // Handle multipart form data
        if ($this->is('multipart/form-data')) {
            $this->parseMultipartFormData($rawRequest);
        }

        // Parse the body
        $this->body = implode("\r\n", $lines);

        // Parse cookies
        $this->parseCookies();

        // Extract hostname and IP
        $this->hostname = parse_url($this->uri, PHP_URL_HOST);
        $this->ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Parses multipart form data from the request.
     *
     * @param string $rawRequest The raw HTTP request string.
     * @throws \InvalidArgumentException If the boundary is missing.
     */
    private function parseMultipartFormData($rawRequest)
    {
        $boundary = $this->getBoundary();
        if (!$boundary) {
            throw new \InvalidArgumentException("Missing boundary in multipart/form-data.");
        }

        $parts = explode("--$boundary", $rawRequest);

        foreach ($parts as $part) {
            if (empty($part) || $part === "--\r\n") {
                continue;
            }

            if (preg_match('/name="([^"]+)"\s*;\s*filename="([^"]+)"/', $part, $matches)) {
                $name = $matches[1];
                $filename = $matches[2];
                $fileContent = substr($part, strpos($part, "\r\n\r\n") + 4);
                $fileContent = trim($fileContent);

                $this->files[$name] = [
                    'filename' => $filename,
                    'content' => $fileContent,
                ];
            }
        }
    }

    /**
     * Extracts the boundary from the Content-Type header.
     *
     * @return string|null The boundary string or null if not found.
     */
    private function getBoundary()
    {
        $contentType = $this->getHeader('Content-Type');
        if (preg_match('/boundary=(.*)$/', $contentType, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Parses cookies from the Cookie header.
     */
    private function parseCookies()
    {
        $cookieHeader = $this->getHeader('Cookie');
        if ($cookieHeader) {
            $cookiePairs = explode('; ', $cookieHeader);
            foreach ($cookiePairs as $cookie) {
                list($name, $value) = explode('=', $cookie);
                $this->cookies[$name] = $value;
            }
        }
    }

    /**
     * Returns the HTTP method of the request.
     *
     * @return string The HTTP method (e.g., GET, POST).
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Returns the request URI.
     *
     * @return string The request URI.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns the HTTP protocol version.
     *
     * @return string The HTTP protocol (e.g., HTTP/1.1).
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * Returns all headers as an associative array.
     *
     * @return array The request headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Returns the request body.
     *
     * @return string The request body.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Sets a header by name and value.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Returns a specific header by name.
     *
     * @param string $name The header name.
     * @return string|null The header value or null if not found.
     */
    public function getHeader($name)
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Returns all uploaded files.
     *
     * @return array An associative array of uploaded files.
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * Returns a specific uploaded file by input name.
     *
     * @param string $name The file input name.
     * @return array|null The file details or null if not found.
     */
    public function getFile($name)
    {
        return $this->files[$name] ?? null;
    }

    /**
     * Returns all query parameters.
     *
     * @return array The query parameters.
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * Returns a specific query parameter by name.
     *
     * @param string $name The query parameter name.
     * @return string|null The query parameter value or null if not found.
     */
    public function getQueryParam($name)
    {
        return $this->queryParams[$name] ?? null;
    }

    /**
     * Extracts path parameters based on a regex pattern.
     *
     * @param string $pattern The regex pattern to match against the URI.
     */
    public function extractPathParams($pattern)
    {
        if (preg_match($pattern, $this->uri, $matches)) {
            // Filter out numeric keys (non-named capture groups)
            $this->pathParams = array_filter($matches, function ($key) {
                return is_string($key);
            }, ARRAY_FILTER_USE_KEY);
        }
    }

    /**
     * Returns all path parameters.
     *
     * @return array The path parameters.
     */
    public function getPathParams()
    {
        return $this->pathParams;
    }

    /**
     * Checks if the request method is GET.
     *
     * @return bool True if the method is GET, false otherwise.
     */
    public function isGet()
    {
        return $this->method === 'GET';
    }

    /**
     * Checks if the request method is POST.
     *
     * @return bool True if the method is POST, false otherwise.
     */
    public function isPost()
    {
        return $this->method === 'POST';
    }

    /**
     * Checks if the request method is PUT.
     *
     * @return bool True if the method is PUT, false otherwise.
     */
    public function isPut()
    {
        return $this->method === 'PUT';
    }

    /**
     * Checks if the request method is DELETE.
     *
     * @return bool True if the method is DELETE, false otherwise.
     */
    public function isDelete()
    {
        return $this->method === 'DELETE';
    }

    /**
     * Returns all cookies.
     *
     * @return array The cookies.
     */
    public function getCookies()
    {
        return $this->cookies;
    }

    /**
     * Returns a specific cookie by name.
     *
     * @param string $name The cookie name.
     * @return string|null The cookie value or null if not found.
     */
    public function getCookie($name)
    {
        return $this->cookies[$name] ?? null;
    }

    /**
     * Returns the hostname from the request URI.
     *
     * @return string The hostname.
     */
    public function getHostname()
    {
        return $this->getHeader('Host') ?? $this->hostname;
    }

    /**
     * Returns the client IP address.
     *
     * @return string The client IP address.
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * Checks if the request content type matches the given type.
     *
     * @param string $type The content type to check (e.g., "application/json").
     * @return bool True if the content type matches, false otherwise.
     */
    public function is($type)
    {
        $contentType = $this->getHeader('Content-Type') ?? '';
        return strpos($contentType, $type) !== false;
    }

    /**
     * Checks if the request accepts the given content type.
     *
     * @param string $type The content type to check (e.g., "application/json").
     * @return bool True if the request accepts the type, false otherwise.
     */
    public function accepts($type)
    {
        $acceptHeader = $this->getHeader('Accept') ?? '';
        return strpos($acceptHeader, $type) !== false;
    }

    /**
     * Checks if the request is using HTTPS.
     *
     * @return bool True if the protocol is HTTPS, false otherwise.
     */
    public function isHttps()
    {
        return !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    }
}