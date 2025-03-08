<?php

namespace PhpHttpServer\Core;

class Server
{
    private $host;
    private $port;
    private $socket;
    private $router;

    public function __construct($host = '0.0.0.0', $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
        $this->router = new Router();
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

                // Log the parsed request
                echo "Request: {$request->getMethod()} {$request->getUri()}\n";

                // Match the request to a route
                $route = $this->router->match($request->getMethod(), $request->getUri());

                if ($route) {
                    // Call the route handler
                    $handler = $route['handler'];
                    $params = $route['params'];

                    $response = new Response();
                    call_user_func_array($handler, [$request, $response, $params]);
                } else {
                    // No route matched, return a 404 response
                    $response = new Response();
                    $response->setStatusCode(404)
                        ->setHeader('Content-Type', 'text/plain')
                        ->setBody('404 Not Found');
                }

                // Send the response
                $response->send($conn);

                // Close the connection
                fclose($conn);

                echo "Response sent and connection closed.\n";
            }
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