<?php

namespace PhpHttpServer\Core;

interface RouterInterface
{
    /**
     * Add a route to the router.
     *
     * @param string $method The HTTP method (e.g., GET, POST).
     * @param string $uri The URI pattern (e.g., /users, /posts/:id).
     * @param callable $handler The handler function for the route.
     * @param array $middleware Route-specific middleware.
     */
    public function addRoute($method, $uri, $handler, $middleware = []);

    /**
     * Add global middleware.
     *
     * @param \PhpHttpServer\Middleware\MiddlewareInterface $middleware The middleware to add.
     */
    public function addGlobalMiddleware(\PhpHttpServer\Middleware\MiddlewareInterface $middleware);

    /**
     * Match a request to a route.
     *
     * @param string $method The HTTP method of the request.
     * @param string $uri The URI of the request.
     * @return array|null The matched route and parameters, or null if no match.
     */
    public function match($method, $uri);

    /**
     * Get the global middleware.
     *
     * @return array The global middleware.
     */
    public function getGlobalMiddleware();
}