<?php

namespace PhpHttpServer\Core;

use PhpHttpServer\Middleware\MiddlewareInterface;

class Router implements RouterInterface
{
    private $routes = [
        'GET' => [],
        'POST' => [],
        'PUT' => [],
        'PATCH' => [],
        'DELETE' => [],
        'OPTIONS' => [],
        'HEAD' => [],
    ];

    private $globalMiddleware = [];

    public function addRoute($method, $uri, $handler, $middleware = [])
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            throw new \InvalidArgumentException("Unsupported HTTP method: $method");
        }

        $this->routes[$method][] = [
            'uri' => $uri,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    public function addGlobalMiddleware(MiddlewareInterface $middleware)
    {
        $this->globalMiddleware[] = $middleware;
    }

    public function match($method, $uri)
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            return null;
        }

        foreach ($this->routes[$method] as $route) {
            if ($this->matchUri($route['uri'], $uri, $params)) {
                return [
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    public function getGlobalMiddleware()
    {
        return $this->globalMiddleware;
    }

    private function matchUri($routeUri, $requestUri, &$params)
    {
        // Convert route URI to a regex pattern
        $pattern = preg_replace('/\//', '\\/', $routeUri); // Escape slashes
        $pattern = preg_replace('/:([a-zA-Z0-9_]+)/', '(?P<\1>[a-zA-Z0-9_]+)', $pattern); // Replace parameters with regex groups
        $pattern = '/^' . $pattern . '$/';

        // Match the request URI against the pattern
        if (preg_match($pattern, $requestUri, $matches)) {
            // Extract named parameters
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = $value;
                }
            }

            return true;
        }

        return false;
    }
}