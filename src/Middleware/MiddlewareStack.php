<?php

namespace PhpHttpServer\Middleware;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;


class MiddlewareStack
{
    private $middlewareStack = [];

    public function addMiddleware(MiddlewareInterface $middleware)
    {
        $this->middlewareStack[] = $middleware;
    }

    public function execute(Request $request, Response $response, callable $finalHandler)
    {
        $middlewareStack = array_reverse($this->middlewareStack);
        $next = $finalHandler;
        foreach ($middlewareStack as $middleware) {
            $next = function (Request $request, Response $response) use ($middleware, $next) {
                $middleware($request, $response, $next);
            };
        }
        $next($request, $response);
    }
}