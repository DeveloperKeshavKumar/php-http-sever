<?php

class WebSocketServer 
{
    private $host;
    private $port;
    private $socket;
    private $clients = [];

    public function __construct($host = '0.0.0.0', $port = 8081)
    {
        $this->host = $host;
        $this->port = $port;
    }

    public function start()
    {
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
        while (true) {
            // Prepare the read array with the server socket and all client sockets
            $read = array_merge([$this->socket], $this->clients);
            $write = $except = null;

            // Use socket_select to monitor sockets for activity
            if (socket_select($read, $write, $except, null) === false) {
                die("socket_select failed: " . socket_strerror(socket_last_error()) . "\n");
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
                @socket_write($client, $responseFrame, strlen($responseFrame));
                echo "Sent response to client.\n";

                // Broadcast the message to all clients
                $this->broadcast($decodedFrame['payload']);
            }
        }
    }

    public function handleConnection($socket)
    {
        $this->clients[] = $socket;

        while (true) {
            $data = @socket_read($socket, 8192, PHP_BINARY_READ); // Suppress warnings
            if ($data === false || $data === '') {
                // Client disconnected
                $this->removeClient($socket);
                break;
            }

            // Decode the WebSocket frame
            $decodedFrame = $this->decodeWebSocketFrame($data);
            if ($decodedFrame === null) {
                echo "Invalid WebSocket frame received.\n";
                $this->removeClient($socket);
                break;
            }

            // Log the decoded frame
            echo "Received WebSocket frame:\n";
            echo "Opcode: " . $decodedFrame['opcode'] . "\n";
            echo "Payload: " . $decodedFrame['payload'] . "\n";

            // Send a response back to the client
            $responseFrame = $this->encodeWebSocketFrame("Server received: " . $decodedFrame['payload']);
            @socket_write($socket, $responseFrame, strlen($responseFrame)); // Suppress warnings
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
            return false;
        }

        // Extract the WebSocket key from the request headers
        if (preg_match('/Sec-WebSocket-Key: (.*)\r\n/', $request, $matches)) {
            $key = trim($matches[1]);
        } else {
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
            return false;
        }

        return true;
    }

    public function removeClient($socket)
    {
        $index = array_search($socket, $this->clients);
        if ($index !== false) {
            unset($this->clients[$index]);
            socket_close($socket);
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

    public function encodeWebSocketFrame($payload)
    {
        $frame = '';
        $payloadLength = strlen($payload);

        // Set the first byte (opcode and flags)
        $frame .= chr(0x81); // 0x81 = text frame (FIN bit set)

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

    public function stop()
    {
        if ($this->socket) {
            socket_close($this->socket);
            echo "WebSocket server stopped.\n";
        }
    }
}

$webSocketServer = new WebSocketServer();
$webSocketServer->start();