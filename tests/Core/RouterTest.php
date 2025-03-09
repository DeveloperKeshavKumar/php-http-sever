<?php

use PhpHttpServer\Core\Router;
use PhpHttpServer\Core\Response;
use PhpHttpServer\Core\Request;
use PhpHttpServer\Middleware\MiddlewareInterface;
use PHPUnit\Framework\TestCase;

class RouterTest extends TestCase
{
    private $router;
    private $response;
    private $request;

    protected function setUp(): void
    {
        // Setup the router and request/response objects for each test
        $this->router = new Router();
        $this->response = new Response();
        $this->request = $this->createMock(Request::class);
    }

    public function testAddGetRoute()
    {
        $this->router->get('/test', function ($req, $res) {
            $res->setBody('GET Route');
        });

        $matchedRoute = $this->router->match('GET', '/test');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('/test', $matchedRoute['uri']);
        $this->assertEquals('GET', $matchedRoute['method']);
    }

    public function testAddPostRoute()
    {
        $this->router->post('/test', function ($req, $res) {
            $res->setBody('POST Route');
        });

        $matchedRoute = $this->router->match('POST', '/test');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('/test', $matchedRoute['uri']);
        $this->assertEquals('POST', $matchedRoute['method']);
    }

    public function testAddPutRoute()
    {
        $this->router->put('/test', function ($req, $res) {
            $res->setBody('PUT Route');
        });

        $matchedRoute = $this->router->match('PUT', '/test');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('/test', $matchedRoute['uri']);
        $this->assertEquals('PUT', $matchedRoute['method']);
    }

    public function testAddFallbackRoute()
    {
        $this->router->fallback('/fallback', function ($req, $res) {
            $res->setBody('Fallback Route');
        });

        $matchedRoute = $this->router->match('GET', '/fallback');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('/fallback', $matchedRoute['uri']);
        $this->assertEquals('FALLBACK', $matchedRoute['method']);
    }

    public function testAddRouteGroup()
    {
        $this->router->addRouteGroup('/group', function ($router) {
            $router->get('/test', function ($req, $res) {
                $res->setBody('Grouped GET Route');
            });
        });

        $matchedRoute = $this->router->match('GET', '/group/test');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('/group/test', $matchedRoute['uri']);
        $this->assertEquals('GET', $matchedRoute['method']);
    }

    public function testAddGlobalMiddleware()
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('__invoke')->willReturnCallback(function ($req, $res, $next) {
            $res->setBody('Global Middleware Applied');
            return $next($req, $res);
        });

        $this->router->addGlobalMiddleware($middleware);

        $this->router->get('/test', function ($req, $res) {
            $res->setBody('Route after Middleware');
        });

        $this->router->handle($this->request, $this->response);

        $this->assertEquals('Global Middleware AppliedRoute after Middleware', $this->response->getBody());
    }

    public function testRouteWithParams()
    {
        $this->router->get('/user/:id', function ($req, $res, $params) {
            $res->setBody('User ID: ' . $params['id']);
        });

        $matchedRoute = $this->router->match('GET', '/user/123');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('User ID: 123', $this->response->getBody());
    }

    public function testHandleMethodWithMiddleware()
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware->method('__invoke')->willReturnCallback(function ($req, $res, $next) {
            $res->setBody('Middleware Applied');
            return $next($req, $res);
        });

        $this->router->addGlobalMiddleware($middleware);

        $this->router->get('/test', function ($req, $res) {
            $res->setBody('Final Route');
        });

        $this->request->method('getMethod')->willReturn('GET');
        $this->request->method('getUri')->willReturn('/test');

        $this->router->handle($this->request, $this->response);

        $this->assertEquals('Middleware AppliedFinal Route', $this->response->getBody());
    }


    public function testMatchingFallbackRoute()
    {
        $this->router->fallback('/404', function ($req, $res) {
            $res->setBody('Fallback Route for Not Found');
        });

        $matchedRoute = $this->router->match('GET', '/nonexistent');
        $this->assertNotNull($matchedRoute);
        $this->assertEquals('Fallback Route for Not Found', $this->response->getBody());
    }
}