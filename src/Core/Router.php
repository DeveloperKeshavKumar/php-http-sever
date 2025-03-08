<?php

namespace PhpHttpServer\Core;

class Router
{
    private $routes = [];

    public function addRoute($method, $uri, $handler)
    {
        $this->routes[] = [
            'method' => strtoupper($method),
            'uri' => $uri,
            'handler' => $handler,
        ];
    }

    public function match($method, $uri)
    {
        foreach ($this->routes as $route) {
            if ($route['method'] === $method && $this->matchUri($route['uri'], $uri, $params)) {
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