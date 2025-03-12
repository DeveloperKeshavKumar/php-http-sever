<?php

namespace PhpHttpServer\Core;

use PhpHttpServer\WebSocket\WebSocketInterface;
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
    private $webSocketPid;
    private $running = true;
    private $socketClosed = false; // Track if the socket has been closed

    public function __construct(
        $host = '0.0.0.0',
        $port = 8080,
        RouterInterface $router,
        array $middlewareStack = [],
        WebSocketInterface $webSocketHandler = null,
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
        // Register signal handlers for SIGINT and SIGTERM
        pcntl_signal(SIGINT, [$this, 'stop']);  // Handle Ctrl+C
        pcntl_signal(SIGTERM, [$this, 'stop']); // Handle termination signals

        // Create a TCP/IP socket
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        if ($this->socket === false) {
            die("Failed to create socket: " . socket_strerror(socket_last_error()) . "\n");
        }

        // Allow reuse of the address
        if (!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1)) {
            die("Failed to set socket option: " . socket_strerror(socket_last_error($this->socket)) . "\n");
        }

        // Bind the socket to the specified host and port
        if (!socket_bind($this->socket, $this->host, $this->port)) {
            die("Failed to bind socket: " . socket_strerror(socket_last_error($this->socket)) . "\n");
        }

        // Start listening for connections
        if (!socket_listen($this->socket)) {
            die("Failed to listen on socket: " . socket_strerror(socket_last_error($this->socket)) . "\n");
        }

        // Set the server socket to non-blocking mode
        socket_set_nonblock($this->socket);

        echo "Server listening on http://{$this->host}:{$this->port}\n";

        // Start WebSocket server in a separate process
        if ($this->webSocketHandler) {
            $this->startWebSocketServer();
        }

        $this->clients[] = $this->socket;

        // Main server loop
        while ($this->running) {
            // Dispatch any pending signals
            pcntl_signal_dispatch();

            // Prepare the read array with the server socket and all client sockets
            $read = array_merge([$this->socket], $this->clients);
            $write = $except = null;

            // Use socket_select to monitor sockets for activity
            $result = @socket_select($read, $write, $except, 1); // 1-second timeout

            if ($result === false) {
                $error = socket_last_error();
                if ($error === SOCKET_EINTR) {
                    // Interrupted by a signal, continue the loop
                    continue;
                }
                die("socket_select failed: " . socket_strerror($error) . "\n");
            }

            if ($result === 0) {
                // Timeout, check if we should stop
                if (!$this->running) {
                    break;
                }
                continue;
            }

            foreach ($read as $conn) {
                if ($conn === $this->socket) {
                    // Accept new connections
                    $newConn = socket_accept($this->socket);
                    if ($newConn !== false) {
                        echo "New connection accepted.\n";
                        $this->clients[] = $newConn;
                    }
                } else {
                    // Handle HTTP or WebSocket request
                    $rawRequest = socket_read($conn, 8192, PHP_BINARY_READ);

                    if ($rawRequest === false || $rawRequest === '') {
                        echo "Connection closed by client.\n";
                        @socket_shutdown($conn, 2);
                        socket_close($conn);
                        $this->clients = array_filter($this->clients, function ($client) use ($conn) {
                            return $client !== $conn;
                        });
                        continue;
                    }

                    $request = new Request($rawRequest);
                    $response = new Response();

                    // Check if it's a WebSocket request
                    if ($this->webSocketHandler && $this->isWebSocketRequest($request)) {
                        echo "Trying WebSocket handshake .....\n";
                        if ($this->webSocketHandler->handshake($conn)) {
                            echo "WebSocket handshake successful.\n";

                            // Handle WebSocket communication in the same process
                            $this->webSocketHandler->handleConnection($conn);
                        } else {
                            echo "WebSocket handshake failed.\n";
                            @socket_shutdown($conn, 2);
                            socket_close($conn);
                            $this->clients = array_filter($this->clients, function ($client) use ($conn) {
                                return $client !== $conn;
                            });
                        }
                    } else {
                        // Handle HTTP request
                        $this->handleHttpRequest($conn, $request);

                        // Close the HTTP connection
                        @socket_shutdown($conn, 2);
                        socket_close($conn);
                        $this->clients = array_filter($this->clients, function ($client) use ($conn) {
                            return $client !== $conn;
                        });
                    }
                }
            }
        }

        // Clean up resources
        $this->stop();
    }

    private function startWebSocketServer()
    {
        $pid = pcntl_fork();
        if ($pid == -1) {
            die("Could not fork WebSocket server process.\n");
        } elseif ($pid) {
            // Parent process
            $this->webSocketPid = $pid;
        } else {
            // Child process
            $this->webSocketHandler->start();
            exit(); // Exit the child process after the WebSocket server stops
        }
    }

    private function isWebSocketRequest(Request $request)
    {
        return $request->getHeader('Upgrade') === 'websocket' &&
            strtolower($request->getHeader('Connection')) === 'upgrade';
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
        if (!$this->running) {
            return;
        }

        $this->running = false;

        // Close all client connections
        foreach ($this->clients as $client) {
            if ($client !== $this->socket) {
                socket_shutdown($this->socket, 2);
                socket_close($client);
                echo "Client connection closed.\n";
            }
        }

        // Close the server socket only if it hasn't been closed already
        if ($this->socket && !$this->socketClosed) {
            socket_close($this->socket);
            $this->socketClosed = true; // Mark the socket as closed
            echo "Server socket closed.\n";
        }

        // Terminate the WebSocket server process
        if ($this->webSocketPid) {
            posix_kill($this->webSocketPid, SIGTERM);
        }

        // Exit the script
        exit(0);
    }
}