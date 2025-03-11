<?php

namespace PhpHttpServer\WebSocket;

class WebSocketServer implements WebSocketInterface
{
    private $host;
    private $port;
    private $socket;
    private $clients = [];
    private $running = true;
    private $socketClosed = false;

    public function __construct($host = '0.0.0.0', $port = 8081)
    {
        $this->host = $host;
        $this->port = $port;
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

        echo "WebSocket server started on ws://{$this->host}:{$this->port}\n";

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

            // Handle new connections
            if (in_array($this->socket, $read)) {
                $newSocket = socket_accept($this->socket);
                if ($newSocket !== false) {
                    // Perform WebSocket handshake
                    if ($this->handshake($newSocket)) {
                        // Add the new client to the clients array
                        $this->clients[] = $newSocket;
                        echo "New client connected. Total clients: " . count($this->clients) . "\n";
                    } else {
                        socket_close($newSocket);
                    }
                }
                // Remove the server socket from the read array
                $read = array_filter($read, function ($socket) {
                    return $socket !== $this->socket;
                });
            }

            // Handle incoming data from clients
            foreach ($read as $client) {
                $data = @socket_read($client, 8192, PHP_BINARY_READ);
                if ($data === false || $data === '') {
                    // Client disconnected
                    $this->removeClient($client);
                    continue;
                }

                // Decode the WebSocket frame
                $decodedFrame = $this->decodeWebSocketFrame($data);
                if ($decodedFrame === null) {
                    echo "Invalid WebSocket frame received.\n";
                    $this->removeClient($client);
                    continue;
                }

                // Log the decoded frame
                echo "Received WebSocket frame:\n";
                echo "Opcode: " . $decodedFrame['opcode'] . "\n";
                echo "Payload: " . $decodedFrame['payload'] . "\n";

                // Send a response back to the client
                $responseFrame = $this->encodeWebSocketFrame("Server received: " . $decodedFrame['payload']);
                if (!socket_write($client, $responseFrame, strlen($responseFrame))) {
                    echo "socket_write failed: " . socket_strerror(socket_last_error()) . "\n";
                    $this->removeClient($client);
                    continue;
                }
                echo "Sent response to client.\n";

                // Broadcast the message to all clients
                $this->broadcast($decodedFrame['payload']);
            }
        }

        // Clean up resources
        $this->stop();
    }

    public function handleConnection($socket)
    {
        $this->clients[] = $socket;

        foreach ($this->clients as $client) {
            $data = @socket_read($client, 8192, PHP_BINARY_READ);
            if ($data === false || $data === '') {
                // Client disconnected
                $this->removeClient($client);
                continue;
            }

            // Decode the WebSocket frame
            $decodedFrame = $this->decodeWebSocketFrame($data);
            if ($decodedFrame === null) {
                echo "Invalid WebSocket frame received.\n";
                $this->removeClient($client);
                continue;
            }

            // Handle close frame from the client
            if ($decodedFrame['opcode'] === 0x8) { // 0x8 = close frame
                echo "Client sent a close frame. Closing connection.\n";
                $this->removeClient($client);
                continue;
            }

            // Log the decoded frame
            echo "Received WebSocket frame:\n";
            echo "Opcode: " . $decodedFrame['opcode'] . "\n";
            echo "Payload: " . $decodedFrame['payload'] . "\n";

            // Send a response back to the client
            $responseFrame = $this->encodeWebSocketFrame("Server received: " . $decodedFrame['payload']);
            if (!@socket_write($client, $responseFrame, strlen($responseFrame))) {
                $error = socket_last_error($client);
                if ($error === SOCKET_EPIPE) {
                    echo "Socket is already closed may be by client. Skipping response.\n";
                } else {
                    echo "socket_write failed: " . socket_strerror($error) . "\n";
                }
                $this->removeClient($client);
                continue;
            }
            echo "Sent response to client.\n";

            // Broadcast the message to all clients
            $this->broadcast($decodedFrame['payload']);
        }
    }

    public function handshake($socket)
    {
        // Read the client's handshake request
        $request = socket_read($socket, 8192);
        if (empty($request)) {
            echo "Empty handshake request.\n";
            return false;
        }

        // Extract the WebSocket key from the request headers
        if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $request, $matches)) {
            $key = trim($matches[1]);
        } else {
            echo "No Sec-WebSocket-Key found.\n";
            return false;
        }

        // Generate the WebSocket accept key
        $acceptKey = base64_encode(sha1($key . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // Prepare the handshake response
        $response = "HTTP/1.1 101 Switching Protocols\r\n";
        $response .= "Upgrade: websocket\r\n";
        $response .= "Connection: Upgrade\r\n";
        $response .= "Sec-WebSocket-Accept: $acceptKey\r\n\r\n";

        // Send the handshake response
        if (!socket_write($socket, $response, strlen($response))) {
            echo "Failed to send handshake response.\n";
            return false;
        }

        echo "WebSocket handshake successful.\n";
        return true;
    }

    public function removeClient($socket)
    {
        $index = array_search($socket, $this->clients);
        if ($index !== false) {
            // Send a close frame before closing the connection
            $this->sendCloseFrame($socket, 1000, 'Server closing connection');

            // Close the socket
            socket_close($socket);

            // Remove the client from the clients array
            unset($this->clients[$index]);
            echo "Client removed. Total clients: " . count($this->clients) . "\n";
        }
    }

    public function broadcast($message)
    {
        $frame = $this->encodeWebSocketFrame($message);
        foreach ($this->clients as $client) {
            socket_write($client, $frame, strlen($frame));
        }
        echo "Message broadcasted to all clients.\n";
    }

    public function decodeWebSocketFrame($frame)
    {
        // Extract the first byte (opcode and flags)
        $firstByte = ord($frame[0]);
        $opcode = $firstByte & 0x0F; // Extract the opcode (lower 4 bits)

        // Extract the second byte (mask and payload length)
        $secondByte = ord($frame[1]);
        $isMasked = ($secondByte & 0x80) !== 0; // Check if the frame is masked
        $payloadLength = $secondByte & 0x7F; // Extract the payload length (lower 7 bits)

        // Handle extended payload lengths
        $offset = 2;
        if ($payloadLength === 126) {
            $payloadLength = unpack('n', substr($frame, $offset, 2))[1];
            $offset += 2;
        } elseif ($payloadLength === 127) {
            $payloadLength = unpack('J', substr($frame, $offset, 8))[1];
            $offset += 8;
        }

        // Extract the masking key (if the frame is masked)
        $maskingKey = '';
        if ($isMasked) {
            $maskingKey = substr($frame, $offset, 4);
            $offset += 4;
        }

        // Extract the payload
        $payload = substr($frame, $offset, $payloadLength);

        // Unmask the payload (if the frame is masked)
        if ($isMasked) {
            $payload = $this->unmaskPayload($payload, $maskingKey);
        }

        return [
            'opcode' => $opcode,
            'payload' => $payload,
        ];
    }

    public function unmaskPayload($payload, $maskingKey)
    {
        $unmaskedPayload = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $unmaskedPayload .= $payload[$i] ^ $maskingKey[$i % 4];
        }
        return $unmaskedPayload;
    }

    public function encodeWebSocketFrame($payload, $opcode = 0x81)
    {
        $frame = '';
        $payloadLength = strlen($payload);

        // Set the first byte (FIN bit and opcode)
        $frame .= chr(0x80 | $opcode); // FIN bit set (0x80) + opcode

        // Set the second byte (mask and payload length)
        if ($payloadLength <= 125) {
            $frame .= chr($payloadLength);
        } elseif ($payloadLength <= 65535) {
            $frame .= chr(126) . pack('n', $payloadLength);
        } else {
            $frame .= chr(127) . pack('J', $payloadLength);
        }

        // Append the payload
        $frame .= $payload;

        return $frame;
    }

    public function sendCloseFrame($socket, $statusCode = 1000, $reason = '')
    {
        // Check if the socket is still writable
        if (socket_last_error($socket) === SOCKET_EPIPE) {
            echo "Socket is already closed. Skipping close frame.\n";
            return;
        }

        // Pack the status code into 2 bytes
        $statusCode = pack('n', $statusCode);

        // Combine the status code and reason
        $payload = $statusCode . $reason;

        // Create the close frame
        $closeFrame = $this->encodeWebSocketFrame($payload, 0x88); // 0x88 = close frame

        // Send the close frame
        if (!@socket_write($socket, $closeFrame, strlen($closeFrame))) {
            $error = socket_last_error($socket);
            if ($error === SOCKET_EPIPE) {
                echo "Socket is already closed may be by client. Skipping close frame.\n";
            } else {
                echo "Failed to send close frame: " . socket_strerror($error) . "\n";
            }
        }
    }

    public function stop()
    {
        if (!$this->running) {
            return;
        }

        $this->running = false;

        // Send close frames to all clients
        foreach ($this->clients as $client) {
            $this->sendCloseFrame($client, 1000, 'Server shutting down');
            socket_close($client);
        }
        echo "Clients connections closed.\n";

        // Close the server socket only if it hasn't been closed already
        if ($this->socket && !$this->socketClosed) {
            socket_close($this->socket);
            $this->socketClosed = true; // Mark the socket as closed
            echo "WebSocket server socket closed.\n";
        }

        // Exit the script
        exit(0);
    }
}