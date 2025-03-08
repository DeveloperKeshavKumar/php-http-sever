<?php

require __DIR__ . '/../src/Core/Server.php';

use PhpHttpServer\Core\Server;

// Create a new server instance
$server = new Server('0.0.0.0', 8080);

// Start the server
$server->start();