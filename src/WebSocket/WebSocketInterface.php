<?php

namespace PhpHttpServer\WebSocket;

interface WebSocketInterface
{
    /**
     * Starts the WebSocket server.
     */
    public function start();

    /**
     * Stops the WebSocket server.
     */
    public function stop();

    /**
     * Performs the WebSocket handshake with a new client.
     *
     * @param resource $socket The client socket resource.
     * @return bool True if the handshake was successful, false otherwise.
     */
    public function handshake($socket);

    /**
     * Handles an incoming WebSocket connection.
     *
     * @param resource $socket The client socket resource.
     * @return void
     */
    public function handleConnection($socket);

    /**
     * Removes a client from the list of connected clients.
     *
     * @param resource $socket The client socket resource to remove.
     */
    public function removeClient($socket);

    /**
     * Broadcasts a message to all connected clients.
     *
     * @param string $message The message to broadcast.
     */
    public function broadcast($message);

    /**
     * Decodes a WebSocket frame.
     *
     * @param string $frame The WebSocket frame to decode.
     * @return array|null An array containing the opcode and payload, or null if the frame is invalid.
     */
    public function decodeWebSocketFrame($frame);

    /**
     * Unmasks a WebSocket payload.
     *
     * @param string $payload The masked payload.
     * @param string $maskingKey The masking key.
     * @return string The unmasked payload.
     */
    public function unmaskPayload($payload, $maskingKey);

    /**
     * Encodes a WebSocket frame.
     *
     * @param string $payload The payload to encode.
     * @return string The encoded WebSocket frame.
     */
    public function encodeWebSocketFrame($payload);
}