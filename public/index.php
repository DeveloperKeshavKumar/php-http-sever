<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpHttpServer\Core\Server;

// Create a new server instance
$server = new Server('127.0.0.1', 8080);

// Start the server
$server->start();