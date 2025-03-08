<?php

namespace PhpHttpServer\Core;

class Server
{
    private $host;
    private $port;
    private $socket;

    public function __construct($host = '0.0.0.0', $port = 8080)
    {
        $this->host = $host;
        $this->port = $port;
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
                // Read the request from the client
                $request = fread($conn, 8192);

                // For now, just print the raw request
                echo "Received request:\n$request\n";

                // Send a basic HTTP response
                $response = "HTTP/1.1 200 OK\r\n";
                $response .= "Content-Type: text/plain\r\n";
                $response .= "Connection: close\r\n\r\n";
                $response .= "Hello, World!";

                fwrite($conn, $response);
                fclose($conn);
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