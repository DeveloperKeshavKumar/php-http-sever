<?php

namespace PhpHttpServer\Middleware;

use PhpHttpServer\Core\Request;
use PhpHttpServer\Core\Response;

class ModifyRequestResponseMiddleware implements MiddlewareInterface
{
    public function __invoke(Request $request, Response $response, callable $next)
    {
        // Modify the request (e.g., add a custom header)
        $request->setHeader('X-Custom-Request-Header', 'Modified by Middleware');

        // Log the modified request headers
        echo "Modified Request Headers:\n";
        print_r($request->getHeaders());

        // Call the next middleware or route handler
        $next($request, $response);

        // Modify the response (e.g., append to the response body)
        $response->sendText($response->getBody() . "Modified by Middleware");

        // Log the modified response body
        echo "Modified Response Body:\n";
        echo $response->getBody() . "\n";
    }
}