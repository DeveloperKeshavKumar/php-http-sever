<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpHttpServer\Core\Server;
use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

// Create a new server instance
$server = new Server('0.0.0.0', 8080);

// Define routes
$server->getRouter()->addRoute('GET', '/', function (Request $request, Response $response) {
    $data = ['name' => 'John Doe'];
    $response->setStatusCode(200)
             ->render(__DIR__ . '/../views/home.php', $data);
});

$server->getRouter()->addRoute('GET', '/users/:id', function (Request $request, Response $response, $params) {
    $userId = $params['id'];
    $response->setStatusCode(200)
        ->sendJson(["User ID" => (int)$userId]);
});

$server->getRouter()->addRoute('POST', '/users', function (Request $request, Response $response) {
    $body = $request->getBody();
    $data = ['message' => 'User created', 'data' => $body];
    $response->setStatusCode(201)
        ->sendJson($data);
});

$server->getRouter()->addRoute('GET', '/about', function (Request $request, Response $response) {
    $html = "<h1>About Us</h1><p>This is the about page.</p>";
    $response->setStatusCode(200)
        ->sendHtml($html);
});

// Start the server
$server->start();