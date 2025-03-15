<?php

namespace PhpHttpServer\Core;
use PhpHttpServer\Template\Grind;

class Response
{
    private $statusCode;
    private $headers;
    private $body;
    private $templateEngine;

    /**
     * Constructor to initialize the response.
     *
     * @param string $body The response body.
     * @param int $statusCode The HTTP status code.
     * @param array $headers The response headers.
     */
    public function __construct($body = '', $statusCode = 200, $headers = [])
    {
        $this->body = $body;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Sets the HTTP status code.
     *
     * @param int $statusCode The HTTP status code.
     * @return self
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * Gets the HTTP status code.
     *
     * @return int The HTTP status code.
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Sets a response header.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return self
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Gets all response headers.
     *
     * @return array The response headers.
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Gets response header.
     *@param string $ name The header name.
     * @return array The response headers.
     */
    public function getHeader($name)
    {
        return $this->headers[$name];
    }

    /**
     * Sets the response body.
     *
     * @param string $body The response body.
     * @return self
     */
    public function setBody($body)
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Gets the response body.
     *
     * @return string The response body.
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Appends a plain text to Body.
     *
     * @param string $content The text to append.
     * @return self
     */
    public function appendBody($content)
    {
        $this->body .= $content;
        return $this;
    }

    /**
     * Sends a plain text response.
     *
     * @param string $text The text to send.
     * @return self
     */
    public function sendText($text)
    {
        return $this->setHeader('Content-Type', 'text/plain')
            ->setBody($text);
    }

    /**
     * Sends a JSON response.
     *
     * @param mixed $data The data to encode as JSON.
     * @return self
     */
    public function sendJson($data)
    {
        return $this->setHeader('Content-Type', 'application/json')
            ->setBody(json_encode($data));
    }

    /**
     * Sends an HTML response.
     *
     * @param string $html The HTML to send.
     * @return self
     */
    public function sendHtml($html)
    {
        return $this->setHeader('Content-Type', 'text/html')
            ->setBody($html);
    }

    /**
     * Sends an OPTIONS response with allowed methods.
     *
     * @param array $allowedMethods The allowed HTTP methods.
     * @return self
     */
    public function sendOptions($allowedMethods)
    {
        return $this->setHeader('Allow', implode(', ', $allowedMethods))
            ->setStatusCode(200)
            ->setBody('');
    }

    /**
     * Sends a HEAD response with optional content length.
     *
     * @param int $contentLength The content length.
     * @return self
     */
    public function sendHead($contentLength = 0)
    {
        return $this->setHeader('Content-Length', $contentLength)
            ->setStatusCode(200)
            ->setBody('');
    }

    /**
     * Set the template engine.
     *
     * @param Grind $templateEngine The template engine instance.
     */
    public function setTemplateEngine(Grind $templateEngine): void
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Get the template engine.
     *
     * @return Grind|null The template engine instance.
     */
    public function getTemplateEngine(): ?Grind
    {
        return $this->templateEngine;
    }

    /**
     * Renders a template file with data and sends it as an HTML response.
     *
     * @param string $template The template file path.
     * @param array $data The data to pass to the template.
     * @return self
     */
    public function render(string $template, array $data = [])
    {
        if (!$this->templateEngine) {
            throw new \RuntimeException("Template engine is not set.");
        }

        $this->body = $this->templateEngine->render($template, $data);
        return $this;
    }

    /**
     * Sends the response to the client.
     *
     * @param resource $conn The connection resource.
     */
    public function send($conn)
    {
        $statusLine = "HTTP/1.1 {$this->statusCode} " . $this->getStatusText($this->statusCode) . "\r\n";
        $headers = '';
        foreach ($this->headers as $name => $value) {
            $headers .= "{$name}: {$value}\r\n";
        }
        $headers .= "Connection: close\r\n";
        $response = $statusLine . $headers . "\r\n" . $this->body;
        socket_write($conn, $response);
    }

    /**
     * Gets the status text for a given status code.
     *
     * @param int $statusCode The HTTP status code.
     * @return string The status text.
     */
    private function getStatusText($statusCode)
    {
        $statusTexts = [
            200 => 'OK',
            201 => 'Created',
            400 => 'Bad Request',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            301 => 'Moved Permanently',
            302 => 'Found',
        ];
        return $statusTexts[$statusCode] ?? 'Unknown Status';
    }

    /**
     * Sets the HTTP status code (alias for setStatusCode).
     *
     * @param int $statusCode The HTTP status code.
     * @return self
     */
    public function status($statusCode)
    {
        return $this->setStatusCode($statusCode);
    }

    /**
     * Sends a file as the response.
     *
     * @param string $filePath The path to the file.
     * @param array $headers Additional headers to set.
     * @return self
     */
    public function sendFile($filePath, $headers = [])
    {
        if (file_exists($filePath)) {
            $this->setHeader('Content-Type', mime_content_type($filePath))
                ->setHeader('Content-Length', filesize($filePath))
                ->setBody(file_get_contents($filePath));

            foreach ($headers as $name => $value) {
                $this->setHeader($name, $value);
            }
        } else {
            $this->setStatusCode(404)
                ->setBody("File not found");
        }
        return $this;
    }

    /**
     * Sends a file as a download.
     *
     * @param string $filePath The path to the file.
     * @param string|null $filename The filename for the download.
     * @return self
     */
    public function download($filePath, $filename = null)
    {
        if (file_exists($filePath)) {
            $filename = $filename ?? basename($filePath);
            $this->setHeader('Content-Type', mime_content_type($filePath))
                ->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
                ->setHeader('Content-Length', filesize($filePath))
                ->setBody(file_get_contents($filePath));
        } else {
            $this->setStatusCode(404)
                ->setBody("File not found");
        }
        return $this;
    }

    /**
     * Sets the Content-Type header.
     *
     * @param string $type The content type.
     * @return self
     */
    public function type($type)
    {
        return $this->setHeader('Content-Type', $type);
    }

    /**
     * Redirects to a URL.
     *
     * @param string $url The URL to redirect to.
     * @param int $statusCode The HTTP status code for the redirect.
     * @return self
     */
    public function redirect($url, $statusCode = 302)
    {
        return $this->setStatusCode($statusCode)
            ->setHeader('Location', $url)
            ->setBody('');
    }

    /**
     * Sets a header (alias for setHeader).
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return self
     */
    public function set($name, $value)
    {
        return $this->setHeader($name, $value);
    }

    /**
     * Appends a value to an existing header or creates a new one.
     *
     * @param string $name The header name.
     * @param string $value The header value.
     * @return self
     */
    public function append($name, $value)
    {
        if (isset($this->headers[$name])) {
            $this->headers[$name] .= ', ' . $value;
        } else {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Adds a Vary header.
     *
     * @param string $field The field to vary on.
     * @return self
     */
    public function vary($field)
    {
        return $this->append('Vary', $field);
    }

    /**
     * Checks if the response content type matches the given type.
     *
     * @param string $type The content type to check.
     * @return bool True if the content type matches, false otherwise.
     */
    public function is($type)
    {
        $contentType = $this->headers['Content-Type'] ?? '';
        return strpos($contentType, $type) !== false;
    }
}