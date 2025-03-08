<?php

namespace PhpHttpServer\Core;

use PhpHttpServer\WebSocket\WebSocketHandlerInterface;

class Server
{
    private $host;
    private $port;
    private $socket;
    private $router;
    private $webSocketHandler;

    public function __construct($host = '0.0.0.0', $port = 8080, RouterInterface $router, WebSocketHandlerInterface $webSocketHandler )
    {
        $this->host = $host;
        $this->port = $port;
        $this->router = $router;
        $this->webSocketHandler = $webSocketHandler;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function start()
    {
        // Create a socket that listens on the specified host and port
        $this->socket = stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);

        if (!$this->socket) {
            die("Failed to create socket: $errstr ($errno)\n");
        }

        echo "Server listening on http://{$this->host}:{$this->port}\n";

        // Main server loop
        while (true) {
            // Accept incoming connections
            $conn = stream_socket_accept($this->socket, -1);

            if ($conn) {
                echo "New connection accepted.\n";

                // Read the request from the client
                $rawRequest = fread($conn, 8192);

                if ($rawRequest === false || $rawRequest === '') {
                    echo "Connection closed by client.\n";
                    fclose($conn);
                    continue;
                }

                echo "Raw request received:\n$rawRequest\n";

                // Parse the request
                $request = new Request($rawRequest);

                // Log the parsed request
                echo "Received request:\n";
                echo "Method: " . $request->getMethod() . "\n";
                echo "URI: " . $request->getUri() . "\n";
                echo "Headers: " . print_r($request->getHeaders(), true) . "\n";
                echo "Body: " . $request->getBody() . "\n";

                // Check if the request is a WebSocket upgrade request
                if ($this->webSocketHandler->handshake($request, $response = new Response())) {
                    echo "WebSocket handshake successful.\n";

                    // Send the WebSocket handshake response
                    $response->send($conn);

                    // Handle WebSocket communication
                    $this->webSocketHandler->handle($conn);
                    continue; // Skip the rest of the loop
                }

                // Handle HTTP requests
                $this->handleHttpRequest($conn, $request);
            }
        }
    }

    /**
     * Handle an HTTP request.
     *
     * @param resource $conn The connection resource.
     * @param Request $request The HTTP request.
     */
    private function handleHttpRequest($conn, Request $request)
    {
        // Match the request to a route
        $route = $this->router->match($request->getMethod(), $request->getUri());

        if ($route) {
            // Create the response
            $response = new Response();

            // Combine global and route-specific middleware
            $middlewareStack = array_merge($this->router->getGlobalMiddleware(), $route['middleware']);

            // Create the final handler (route handler)
            $finalHandler = function (Request $request, Response $response) use ($route) {
                call_user_func_array($route['handler'], [$request, $response, $route['params']]);
            };

            // Build the middleware stack
            $middlewareStack = array_reverse($middlewareStack);
            $next = $finalHandler;
            foreach ($middlewareStack as $middleware) {
                $next = function (Request $request, Response $response) use ($middleware, $next) {
                    $middleware($request, $response, $next);
                };
            }

            // Execute the middleware stack
            $next($request, $response);

            // Send the response
            $response->send($conn);
        } else {
            // No route matched, return a 404 response
            $response = new Response();
            $response->setStatusCode(404)
                ->sendText('404 Not Found')
                ->send($conn);
        }

        // Close the connection
        fclose($conn);

        echo "Response sent and connection closed.\n";
    }

    public function stop()
    {
        if ($this->socket) {
            fclose($this->socket);
            echo "Server stopped.\n";
        }
    }
}