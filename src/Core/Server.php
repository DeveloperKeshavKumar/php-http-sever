<?php

namespace PhpHttpServer\Core;

use PhpHttpServer\WebSocket\WebSocketHandlerInterface;
use PhpHttpServer\Middleware\MiddlewareStack;
use PhpHttpServer\Cache\CacheInterface;
use PhpHttpServer\Cache\Cache;

class Server
{
    private $host;
    private $port;
    private $socket;
    private $router;
    private $middlewareStack;
    private $webSocketHandler;
    private $clients = [];
    private $cache;

    public function __construct(
        $host = '0.0.0.0',
        $port = 8080,
        RouterInterface $router,
        array $middlewareStack = [],
        WebSocketHandlerInterface $webSocketHandler = null,
        CacheInterface $cache = null
    ) {
        $this->host = $host;
        $this->port = $port;
        $this->router = $router;
        $this->middlewareStack = $middlewareStack;
        $this->webSocketHandler = $webSocketHandler;
        $this->cache = $cache ?? new Cache();
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

        $this->clients[] = $this->socket;

        // Main server loop
        while (true) {
            // Filter out invalid resources
            $read = array_filter($this->clients, function ($conn) {
                return is_resource($conn);
            });

            $write = $except = null;

            // Wait for activity on any of the sockets
            if (stream_select($read, $write, $except, null) === false) {
                die("stream_select failed.\n");
            }

            foreach ($read as $conn) {
                if ($conn === $this->socket) {
                    // Accept new connections
                    $newConn = stream_socket_accept($this->socket);
                    if ($newConn) {
                        echo "New connection accepted.\n";
                        $this->clients[] = $newConn;
                    }
                } else {
                    // Handle HTTP or WebSocket request
                    $rawRequest = fread($conn, 8192);

                    if ($rawRequest === false || $rawRequest === '') {
                        echo "Connection closed by client.\n";
                        fclose($conn);
                        $this->clients = array_diff($this->clients, [$conn]);
                        continue;
                    }

                    $request = new Request($rawRequest);
                    $response = new Response();

                    // Check if it's a WebSocket request
                    if ($this->webSocketHandler && $this->webSocketHandler->handshake($request, $response)) {
                        $response->send($conn);
                        echo "WebSocket handshake successful.\n";

                        // Fork a process to handle WebSocket communication
                        $pid = pcntl_fork();

                        if ($pid === -1) {
                            // Fork failed
                            die("Could not fork process\n");
                        } elseif ($pid === 0) {
                            // Child Process: Handles WebSocket
                            fclose($this->socket); // Close parent socket in child process
                            $this->webSocketHandler->handle($conn);
                            exit(0);
                        } else {
                            // Parent Process: Keeps Listening
                            fclose($conn); // Close client socket in parent process
                        }

                        // Remove the WebSocket connection from the clients array in parent
                        $this->clients = array_diff($this->clients, [$conn]);
                    } else {
                        // Handle HTTP request
                        $this->handleHttpRequest($conn, $request);

                        // Close the HTTP connection
                        fclose($conn);
                        $this->clients = array_diff($this->clients, [$conn]);
                    }
                }
            }
        }
    }

    private function handleHttpRequest($conn, Request $request)
    {
        $route = $this->router->match($request->getMethod(), $request->getUri());

        if ($route) {
            // Generate a unique cache key for the request
            $cacheKey = $request->getMethod() . ':' . $request->getUri();

            // Try to get the response from the cache
            $cachedResponse = $this->cache->get($cacheKey);

            if ($cachedResponse !== null) {
                // Serve the cached response
                $cachedResponse->send($conn);
                return;
            }

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

            // Cache the response for future requests
            $this->cache->set($cacheKey, $response, 60); // Cache for 60 seconds

            // Send the response
            $response->send($conn);
        } else {
            // No route matched, return a 404 response
            $response = new Response();
            $response->setStatusCode(404)
                ->sendText('404 Not Found')
                ->send($conn);
        }
    }

    public function stop()
    {
        if ($this->socket) {
            fclose($this->socket);
            echo "Server stopped.\n";
        }
    }
}