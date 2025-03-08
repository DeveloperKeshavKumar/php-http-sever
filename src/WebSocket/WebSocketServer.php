<?php

namespace PhpHttpServer\WebSocket;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

class WebSocketServer
{
    public function handshake(Request $request, Response $response)
    {
        // Check if the request is a WebSocket upgrade request
        if (
            $request->getHeader('Upgrade') !== 'websocket' ||
            strtolower($request->getHeader('Connection')) !== 'upgrade'
        ) {
            return false; // Not a WebSocket upgrade request
        }

        // Get the WebSocket key from the request
        $secWebSocketKey = $request->getHeader('Sec-WebSocket-Key');
        if (empty($secWebSocketKey)) {
            return false; // Missing WebSocket key
        }

        // Generate the WebSocket accept key
        $secWebSocketAccept = base64_encode(sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));

        // Set the response headers for the WebSocket handshake
        $response->setStatusCode(101)
            ->setHeader('Upgrade', 'websocket')
            ->setHeader('Connection', 'Upgrade')
            ->setHeader('Sec-WebSocket-Accept', $secWebSocketAccept);

        return true; // Handshake successful
    }
}