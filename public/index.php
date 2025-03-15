<?php

require __DIR__ . '/../vendor/autoload.php';

use PhpHttpServer\Core\Router;
use PhpHttpServer\Core\Server;
use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;
use PhpHttpServer\Middleware\ExampleMiddleware;
use PhpHttpServer\Middleware\ModifyRequestResponseMiddleware;
use PhpHttpServer\WebSocket\WebSocketServer;
use PhpHttpServer\Template\Grind;

if (!extension_loaded('pcntl')) {
    die('The pcntl extension is not available. You need to install it to fork processes.');
}

// Initialize the router
$router = new Router();
$templateEngine = new Grind(__DIR__ . '/../views');

// Set the view engine globally
$router->setViewEngine($templateEngine);

// Middleware stack (global middleware applied to all routes)
$middlewareStack = [
    new ExampleMiddleware('Global Middleware') // Example of global middleware
];

// Initialize WebSocket server (if you have WebSocket support)
$webSocketServer = new WebSocketServer();

// Create the server instance
$server = new Server('0.0.0.0', 8080, $router, $middlewareStack, $webSocketServer);

// Add a route group for `/api`
$router->addRouteGroup('/api', function (Router $router) {

    // Add a GET route: /api/test
    $router->get('/test', function (Request $request, Response $response) {
        $response->setStatusCode(200)
            ->sendText('This is a test route under /api.');
    });

    // Add a GET route: /api/users/:id
    $router->get('/users/:id', function (Request $request, Response $response, $params) {
        $userId = $params['id'];
        $response->setStatusCode(200)
            ->sendJson(["User ID" => (int) $userId]);
    });

    // Add a POST route: /api/users
    $router->post('/users', function (Request $request, Response $response) {
        $body = $request->getBody();
        $data = ['message' => 'User created', 'data' => $body];
        $response->setStatusCode(201)
            ->sendJson($data);
    });

    // Add a GET route: /api/about
    $router->get('/about', function (Request $request, Response $response) {
        $html = "<h1>About Us</h1><p>This is the about page under /api.</p>";
        $response->setStatusCode(200)
            ->sendHtml($html);
    });

}, [new ExampleMiddleware('API Group Middleware')]);  // Apply middleware to the group

// Add a route group for `/posts`
$router->addRouteGroup('/posts', function (Router $router) {

    // Add a GET route: /posts/:postId/comments/:commentId
    $router->get('/:postId/comments/:commentId', function (Request $request, Response $response, $params) {
        $postId = $params['postId'];
        $commentId = $params['commentId'];
        $response->setStatusCode(200)
            ->sendText("Post ID: $postId, Comment ID: $commentId");
    });

}, [new ExampleMiddleware('Posts Group Middleware')]);  // Apply middleware to the group

// Add a GET route: /
$router->get('/', function (Request $request, Response $response) {
    $data = ['test' => 'Test page','rawtest'=>'<a href="/api/test">go to test</a>'];
    echo "Route Handler: Handling the request.\n";
    $response->setStatusCode(200)
        ->render('/home.grd', $data);
});

$router->get('/download', function (Request $request, Response $response) {
    $filePath = __DIR__ . '/assets/index.js';
    $response->sendFile($filePath, );
});

// Add OPTIONS route: /users
$router->options('/users', function (Request $request, Response $response) {
    $response->sendOptions(['GET', 'HEAD', 'OPTIONS']);
});

// Add HEAD route: /users/:id
$router->head('/users/:id', function (Request $request, Response $response, $params) {
    $userId = $params['id'];

    // Simulate a check for user existence
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