<?php

namespace PhpHttpServer\Tests\Middleware;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;
use PhpHttpServer\Middleware\MiddlewareInterface;

class TestMiddleware implements MiddlewareInterface
{
    private $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * Process the request and response.
     *
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param callable $next The next middleware or route handler.
     * @return void
     */
    public function __invoke(Request $request, Response $response, callable $next)
    {
        // Log that this middleware was executed
        $response->appendBody("Middleware {$this->name} executed.\n");

        // Call the next middleware or route handler
        $next($request, $response);
    }
}