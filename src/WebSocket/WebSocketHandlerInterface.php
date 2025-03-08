<?php

namespace PhpHttpServer\WebSocket;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

interface WebSocketHandlerInterface
{
    /**
     * Perform the WebSocket handshake.
     *
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @return bool True if the handshake was successful, false otherwise.
     */
    public function handshake(Request $request, Response $response);

    /**
     * Handle a WebSocket connection.
     *
     * @param resource $conn The connection resource.
     */
    public function handle($conn);
}