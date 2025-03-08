<?php

namespace PhpHttpServer\Middleware;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

interface MiddlewareInterface
{
    /**
     * Process the request and response.
     *
     * @param Request $request The HTTP request.
     * @param Response $response The HTTP response.
     * @param callable $next The next middleware or route handler.
     * @return void
     */
    public function __invoke(Request $request, Response $response, callable $next);
}