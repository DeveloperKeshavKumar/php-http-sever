<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpHttpServer\Core\Server;
use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

use PhpHttpServer\Middleware\ExampleMiddleware;

// Add global middleware

// Create a new server instance
$server = new Server('0.0.0.0', 8080);

// Define routes
$server->getRouter()->addGlobalMiddleware(new ExampleMiddleware('Global1'));
$server->getRouter()->addGlobalMiddleware(new ExampleMiddleware('Global2'));

$server->getRouter()->addRoute('GET', '/test', function (Request $request, Response $response) {
    $response->setStatusCode(200)
        ->sendText('This is a test route.');
}, [new ExampleMiddleware('Route-Specific'), new ExampleMiddleware('Route-Specific2')]);

$server->getRouter()->addRoute('GET', '/', function (Request $request, Response $response) {
    $data = ['name' => 'John Doe'];
    echo "Route Handler: Handling the request.\n";
    $response->setStatusCode(200)
        ->render(__DIR__ . '/../views/home.php', $data);
});

$server->getRouter()->addRoute('GET', '/users/:id', function (Request $request, Response $response, $params) {
    $userId = $params['id'];
    $response->setStatusCode(200)
        ->sendJson(["User ID" => (int) $userId]);
});

$server->getRouter()->addRoute('GET', '/posts/:postId/comments/:commentId', function (Request $request, Response $response, $params) {
    $postId = $params['postId'];
    $commentId = $params['commentId'];
    $response->setStatusCode(200)
        ->sendText("Post ID: $postId, Comment ID: $commentId");
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

$server->getRouter()->addRoute('GET', '/test', function (Request $request, Response $response) {
    $response->setStatusCode(200)
        ->sendText('This is a test route.');
});

$server->getRouter()->addRoute('OPTIONS', '/users', function (Request $request, Response $response) {
    $response->sendOptions(['GET', 'HEAD', 'OPTIONS']);
});

$server->getRouter()->addRoute('HEAD', '/users/:id', function (Request $request, Response $response, $params) {
    $userId = $params['id'];

    $userExists = true;

    if ($userExists) {
        $response->setHeader('Content-Type', 'text/plain')
            ->sendHead(strlen("User ID: $userId"));
    } else {
        $response->setStatusCode(404)
            ->sendHead();
    }
});

// Start the server
$server->start();