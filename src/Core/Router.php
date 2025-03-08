<?php

namespace PhpHttpServer\Core;

class Router
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

    public function addRoute($method, $uri, $handler)
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            throw new \InvalidArgumentException("Unsupported HTTP method: $method");
        }

        // Add the route to the appropriate method group
        $this->routes[$method][] = [
            'uri' => $uri,
            'handler' => $handler,
        ];
    }

    public function match($method, $uri)
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            return null;
        }

        // Iterate through routes for the specific HTTP method
        foreach ($this->routes[$method] as $route) {
            if ($this->matchUri($route['uri'], $uri, $params)) {
                return [
                    'handler' => $route['handler'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    private function matchUri($routeUri, $requestUri, &$params)
    {
        // Convert route URI to a regex pattern
        $pattern = preg_replace('/\//', '\\/', $routeUri); // Escape slashes
        $pattern = preg_replace(pattern: '/:([a-zA-Z0-9_]+)/', replacement: '(?P<\1>[a-zA-Z0-9_]+)', subject: $pattern);
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