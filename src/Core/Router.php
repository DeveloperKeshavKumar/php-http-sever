<?php

namespace PhpHttpServer\Core;

use PhpHttpServer\Middleware\MiddlewareInterface;
use PhpHttpServer\Template\Grind;

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
        'FALLBACK' => [],
    ];

    private $globalMiddleware = [];
    private $middlewareStack = [];  // Stack for managing middleware scopes

    private static $compiledPatterns = [];  // Cache for compiled route patterns

    private $currentPrefix = '';
    private $templateEngine;

    /**
     * Adds a GET route.
     */
    public function get($uri, $handler, $middleware = [])
    {
        $this->addRoute('GET', $uri, $handler, $middleware);
    }

    /**
     * Adds a POST route.
     */
    public function post($uri, $handler, $middleware = [])
    {
        $this->addRoute('POST', $uri, $handler, $middleware);
    }

    /**
     * Adds a PUT route.
     */
    public function put($uri, $handler, $middleware = [])
    {
        $this->addRoute('PUT', $uri, $handler, $middleware);
    }

    /**
     * Adds a PATCH route.
     */
    public function patch($uri, $handler, $middleware = [])
    {
        $this->addRoute('PATCH', $uri, $handler, $middleware);
    }

    /**
     * Adds a DELETE route.
     */
    public function delete($uri, $handler, $middleware = [])
    {
        $this->addRoute('DELETE', $uri, $handler, $middleware);
    }

    /**
     * Adds an OPTIONS route.
     */
    public function options($uri, $handler, $middleware = [])
    {
        $this->addRoute('OPTIONS', $uri, $handler, $middleware);
    }

    /**
     * Adds a HEAD route.
     */
    public function head($uri, $handler, $middleware = [])
    {
        $this->addRoute('HEAD', $uri, $handler, $middleware);
    }

    /**
     * Adds a fallback route (e.g., for 404 handlers).
     */
    public function fallback($uri, $handler, $middleware = [])
    {
        $this->addRoute('FALLBACK', $uri, $handler, $middleware);
    }

    /**
     * Adds a route to the router.
     */
    public function addRoute($method, $uri, $handler, $middleware = [])
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            throw new \InvalidArgumentException("Unsupported HTTP method: $method");
        }

        // Apply prefix if set
        $prefixedUri = ($this->currentPrefix ?? '') . '/' . ltrim($uri, '/');

        // Compile route pattern
        $pattern = $this->compileRoutePattern($prefixedUri);

        // Merge group middleware with route-specific middleware
        $groupMiddleware = [];
        foreach ($this->middlewareStack as $stack) {
            $groupMiddleware = array_merge($groupMiddleware, $stack);
        }
        $middleware = array_merge($groupMiddleware, $middleware);

        // Store the route
        $this->routes[$method][] = [
            'uri' => $prefixedUri,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    /**
     * Compiles a route URI into a regex pattern.
     */
    private function compileRoutePattern($uri)
    {
        if (isset(self::$compiledPatterns[$uri])) {
            return self::$compiledPatterns[$uri];
        }

        // Escape slashes in the URI
        $pattern = preg_replace('/\//', '\\/', $uri);

        // Replace parameters with regex groups
        $pattern = preg_replace_callback('/:([a-zA-Z0-9_]+)(\(([^)]+)\))?/', function ($matches) {
            $param = $matches[1];
            $regex = isset($matches[3]) ? $matches[3] : '[a-zA-Z0-9_]+';

            // Validate the custom regex
            if (@preg_match('/' . $regex . '/', '') === false) {
                throw new \InvalidArgumentException("Invalid regex pattern for parameter: $param");
            }

            return "(?P<$param>$regex)";
        }, $pattern);

        $pattern = '/^' . $pattern . '$/';
        self::$compiledPatterns[$uri] = $pattern;

        return $pattern;
    }

    /**
     * Adds a group of routes under a common prefix.
     */
    public function addRouteGroup($prefix, $callback, $groupMiddleware = [])
    {
        // Remove trailing slash to keep consistency
        $prefix = rtrim($prefix, '/');

        // Push the prefix to the stack
        $this->middlewareStack[] = $groupMiddleware;

        // Temporary store old prefix
        $oldPrefix = $this->currentPrefix ?? '';

        // Update current prefix
        $this->currentPrefix = $oldPrefix . $prefix;

        // Execute callback with prefixed routes
        $callback($this);

        // Restore previous prefix
        $this->currentPrefix = $oldPrefix;

        // Pop middleware stack
        array_pop($this->middlewareStack);
    }

    /**
     * Matches the incoming request URI to a registered route.
     */
    public function match($method, $uri)
    {
        $method = strtoupper($method);
        if (!isset($this->routes[$method])) {
            return null;
        }

        // Look for a matching route
        foreach ($this->routes[$method] as $route) {
            if ($this->matchUri($route['pattern'], $uri, $params)) {
                return [
                    'uri' => $route['uri'],
                    'method' => $method,
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                    'params' => $params,
                ];
            }
        }

        // Match fallback routes only if no other route matches
        foreach ($this->routes['FALLBACK'] as $route) {
            if ($this->matchUri($route['pattern'], $uri, $params)) {
                return [
                    'uri' => $route['uri'],
                    'method' => 'FALLBACK',
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    /**
     * Matches the request URI to a specific route pattern.
     */
    private function matchUri($pattern, $requestUri, &$params)
    {
        if (preg_match($pattern, $requestUri, $matches)) {
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

    /**
     * Adds global middleware to be applied to all routes.
     */
    public function addGlobalMiddleware(MiddlewareInterface $middleware)
    {
        $this->globalMiddleware[] = $middleware;
    }

    /**
     * Gets the global middleware.
     */
    public function getGlobalMiddleware()
    {
        return $this->globalMiddleware;
    }

    /**
     * Handles the incoming request by matching the route and applying middleware.
     */
    public function handle($request, $response)
    {
        $method = $request->getMethod();
        $uri = $request->getUri();

        // Match the route
        $matchedRoute = $this->match($method, $uri);
        if (!$matchedRoute) {
            // No route matched, return a 404 response
            $response->setStatusCode(404);
            $response->setBody('Not Found');
            return;
        }

        // Define the final handler (the route handler)
        $finalHandler = function ($request, $response) use ($matchedRoute) {
            call_user_func($matchedRoute['handler'], $request, $response, $matchedRoute['params']);
        };

        // Merge global middleware with route-specific middleware
        $middlewareList = array_merge($this->globalMiddleware, $matchedRoute['middleware']);

        // Apply the middleware pipeline
        $this->applyMiddleware($middlewareList, $request, $response, $finalHandler);
    }

    /**
     * Applies the given middleware to the current request.
     */
    private function applyMiddleware($middlewareList, $request, $response, $finalHandler)
    {
        // Create a pipeline of middleware
        $pipeline = array_reduce(
            array_reverse($middlewareList), // Reverse the middleware list to build the pipeline correctly
            function ($next, $middleware) {
                return function ($request, $response) use ($middleware, $next) {
                    // Execute the middleware and pass the next handler
                    return $middleware($request, $response, $next);
                };
            },
            $finalHandler // The final handler is the route handler
        );

        // Start the middleware pipeline
        $pipeline($request, $response);
    }

    /**
     * Set the template engine globally.
     *
     * @param Grind $templateEngine The template engine instance.
     */
    public function setViewEngine(Grind $templateEngine): void
    {
        $this->templateEngine = $templateEngine;
    }

    /**
     * Get the template engine.
     *
     * @return Grind|null The template engine instance.
     */
    public function getViewEngine(): ?Grind
    {
        return $this->templateEngine;
    }
}