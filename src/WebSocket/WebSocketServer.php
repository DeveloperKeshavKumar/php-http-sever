<?php

namespace PhpHttpServer\WebSocket;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

class WebSocketServer
{
    /**
     * Perform the WebSocket handshake.
     *
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @return bool True if the handshake was successful, false otherwise.
     */
    public function handshake(Request $request, Response $response)
    {
        // Check if the request is a WebSocket upgrade request
        if (
            $request->getHeader('Upgrade') !== 'websocket' ||
            strtolower($request->getHeader('Connection')) !== 'upgrade'
        ) {
            return false;
        }

        // Get the WebSocket key from the request
        $secWebSocketKey = $request->getHeader('Sec-WebSocket-Key');
        if (empty($secWebSocketKey)) {
            return false;
        }

        // Generate the WebSocket accept key
        $secWebSocketAccept = base64_encode(sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // Set the response headers for the WebSocket handshake
        $response->setStatusCode(101)
            ->setHeader('Upgrade', 'websocket')
            ->setHeader('Connection', 'Upgrade')
            ->setHeader('Sec-WebSocket-Accept', $secWebSocketAccept);

        return true;
    }

    /**
     * Handle WebSocket communication.
     *
     * @param resource $conn The connection resource.
     */
    public function handleWebSocketConnection($conn)
    {
        echo "WebSocket connection established.\n";

        // Main WebSocket loop
        while (true) {
            // Read a WebSocket frame from the client
            $frame = fread($conn, 8192);

            if ($frame === false || $frame === '') {
                echo "WebSocket connection closed by client.\n";
                break;
            }

            // Decode the WebSocket frame
            $decodedFrame = $this->decodeWebSocketFrame($frame);

            if ($decodedFrame === null) {
                echo "Invalid WebSocket frame received.\n";
                break;
            }

            // Log the decoded frame
            echo "Received WebSocket frame:\n";
            echo "Opcode: " . $decodedFrame['opcode'] . "\n";
            echo "Payload: " . $decodedFrame['payload'] . "\n";

            // Send a response back to the client
            $responseFrame = $this->encodeWebSocketFrame("Server received: " . $decodedFrame['payload']);
            fwrite($conn, $responseFrame);
        }

        // Close the WebSocket connection
        fclose($conn);
        echo "WebSocket connection closed.\n";
    }

    /**
     * Decode a WebSocket frame.
     *
     * @param string $frame The WebSocket frame.
     * @return array|null The decoded frame (opcode and payload), or null if the frame is invalid.
     */
    private function decodeWebSocketFrame($frame)
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

    /**
     * Unmask a WebSocket payload.
     *
     * @param string $payload The masked payload.
     * @param string $maskingKey The masking key.
     * @return string The unmasked payload.
     */
    private function unmaskPayload($payload, $maskingKey)
    {
        $unmaskedPayload = '';
        for ($i = 0; $i < strlen($payload); $i++) {
            $unmaskedPayload .= $payload[$i] ^ $maskingKey[$i % 4];
        }
        return $unmaskedPayload;
    }

    /**
     * Encode a WebSocket frame.
     *
     * @param string $payload The payload to encode.
     * @return string The encoded WebSocket frame.
     */
    private function encodeWebSocketFrame($payload)
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
}