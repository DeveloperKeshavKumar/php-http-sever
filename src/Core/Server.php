<?php

namespace PhpHttpServer\Core;

use PhpHttpServer\WebSocket\WebSocketHandlerInterface;
use PhpHttpServer\Middleware\MiddlewareStack;

class Server
{
    private $host;
    private $port;
    private $socket;
    private $router;
    private $middlewareStack;
    private $webSocketHandler;

    public function __construct(
        $host = '0.0.0.0',
        $port = 8080,
        RouterInterface $router,
        array $middlewareStack = [],
        WebSocketHandlerInterface $webSocketHandler = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->router = $router;
        $this->middlewareStack = $middlewareStack;
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

                // Parse the request
                $request = new Request($rawRequest);

                // Check if the request is a WebSocket upgrade request
                if ($this->webSocketHandler && $this->webSocketHandler->handshake($request, $response = new Response())) {
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

    private function handleHttpRequest($conn, Request $request)
    {
        $route = $this->router->match($request->getMethod(), $request->getUri());

        if ($route) {
            $response = new Response();

            // Combine global and route-specific middleware
            $middlewareStack = new MiddlewareStack();
            foreach ($this->middlewareStack as $middleware) {
                $middlewareStack->addMiddleware($middleware);
            }
            foreach ($route['middleware'] as $middleware) {
                $middlewareStack->addMiddleware($middleware);
            }

            // Execute the middleware stack
            $middlewareStack->execute($request, $response, function (Request $request, Response $response) use ($route) {
                call_user_func_array($route['handler'], [$request, $response, $route['params']]);
            });

            // Send the response
            $response->send($conn);
        } else {
            // No route matched, return a 404 response
            $response = new Response();
            $response->setStatusCode(404)
                ->sendText('404 Not Found')
                ->send($conn);
        }

        fclose($conn);
    }

    public function stop()
    {
        if ($this->socket) {
            fclose($this->socket);
            echo "Server stopped.\n";
        }
    }
}