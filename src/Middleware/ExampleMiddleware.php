<?php

namespace PhpHttpServer\Middleware;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

class ExampleMiddleware implements MiddlewareInterface
{
    private $type;

    public function __construct($type = 'global')
    {
        $this->type = $type;
    }

    public function __invoke(Request $request, Response $response, callable $next)
    {
        // Perform actions before the request is handled
        echo "{$this->type} Middleware: Before handling the request.\n";

        // Call the next middleware or route handler
        $next($request, $response);

        // Perform actions after the request is handled
        echo "{$this->type} Middleware: After handling the request.\n";
    }
}