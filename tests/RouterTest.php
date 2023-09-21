cs
<?php 

use PHPUnit\Framework\TestCase;
use SigmaPHP\Router\Router;
use SigmaPHP\Router\Exceptions\RouteNotFoundException;
use SigmaPHP\Router\Exceptions\InvalidArgumentException;
use SigmaPHP\Router\Exceptions\DuplicatedRoutesException;
use SigmaPHP\Router\Exceptions\ActionIsNotDefinedException;
use SigmaPHP\Router\Exceptions\DuplicatedRouteNamesException;

require('ExampleController.php');
require('ExampleMiddleware.php');
require('route_handlers.php');

/**
 * Router Test
 * 
 * Please Note : through this test unit you find a lot of 
 * altering for $_SERVER values , for sake of simplicity
 * we use this cheap trick to test the router :D
 */
class RouterTest extends TestCase
{
    /**
     * @var array $routes
     */
    private $routes;

    /**
     * RouterTest SetUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        
        // define testing routes array
        $this->routes = [
            [
                'name' => 'test1',
                'path' => '/test1/static',
                'method' => 'get',
                'action' => 'route_handler_a',
            ],
            [
                'name' => 'test2',
                'path' => '/test2/{data}',
                'method' => 'get',
                'action' => 'route_handler_b'
            ],
            [
                'name' => 'test3',
                'path' => '/test3/{data1}/{data2}/test/{data3}',
                'method' => 'any',
                'action' => 'route_handler_c'
            ],
            [
                'name' => 'test4',
                'path' => '/test4/optional/param/{data?}',
                'method' => 'get',
                'action' => 'route_handler_d'
            ],
            [
                'name' => 'test5',
                'path' => '/basic/{bar?}',
                'method' => 'put',
                'middlewares' => [
                    [MyMiddleware::class, 'handler'],
                    [AnotherMiddleware::class, 'handler']
                ],
                'controller' => BasicController::class,
                'action' => 'foo'
            ],
            [
                'name' => 'test6',
                'path' => '/get/user/age/{age?}',
                'method' => 'get',
                'action' => 'test_validate_handler',
                'validation' => [
                    'age' => '[0-9]+'
                ]
            ],
            [
                'group' => 'api',
                'path' => 'api/',
                'middlewares' => [
                    [MyMiddleware::class, 'handler'],
                ],
                'routes' => [
                    [
                        'name' => 'posts1',
                        'path' => '/posts',
                        'method' => 'post',
                        'middlewares' => [
                            [AnotherMiddleware::class, 'handler']
                        ],
                        'controller' => BasicController::class,
                        'action' => 'test'
                    ],
                    [
                        'name' => 'posts2',
                        'path' => 'posts/{id?}',
                        'method' => 'get',
                        'controller' => BasicController::class,
                        'action' => 'index'
                    ],
                ]
            ]
        ];
    }

    /**
     * Test router can parse static URLs.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanParseStaticURLs()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("some data");
    }

    /**
     * Test router can parse single parameter URLs.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanParseSingleParameterURLs()
    {
        $_SERVER['REQUEST_URI'] = '/test2/data';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("data was received");
    }

    /**
     * Test router can parse multiple parameters URLs.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanParseMultipleParametersURLs()
    {
        $_SERVER['REQUEST_URI'] = '/test3/d1/d2/test/d3';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("d1 , d2 and d3 were received");
    }

    /**
     * Test router can parse optional parameter URLs.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanParseOptionalParametersURLs()
    {
        $_SERVER['REQUEST_URI'] = '/test4/optional/param/data';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("data");
    }
    
    /**
     * Test router can parse omitted optional parameter.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanParseOmittedOptionalParameter()
    {
        $_SERVER['REQUEST_URI'] = '/test4/optional/param';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("nothing was received");
    }

    /**
     * Test router will through exception if no routes were provided.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfNoRoutesWereProvided()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $router = new Router([]);
    }

    /**
     * Test router will remove the base path from the route.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillRemoveTheBasePathFromTheRoute()
    {
        $_SERVER['REQUEST_URI'] = 'my_host/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes, 'my_host');

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("some data");
    }

    /**
     * Test router will through exception if the route names are duplicated.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfRouteNamesAreDuplicated()
    {
        $this->expectException(DuplicatedRouteNamesException::class);
        
        $duplicatedRoutes = array_merge($this->routes, [
            [
                'name' => 'test1',
                'path' => '/test/duplicated/route',
                'method' => 'get',
                'action' => 'route_handler_a',
            ]
        ]);

        $router = new Router($duplicatedRoutes);
    }

    /**
     * Test router will through exception if the route paths are duplicated.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfRoutePathsAreDuplicated()
    {
        $this->expectException(DuplicatedRoutesException::class);

        $duplicatedRoutes = array_merge($this->routes, [
            [
                'name' => 'testX',
                'path' => '/test1/static',
                'method' => 'get',
                'action' => 'route_handler_a',
            ]
        ]);  
        
        $router = new Router($duplicatedRoutes);
    }

    // test controller
    // test middlewares
    // test exceptions for both
    // test not found page
    // test not found page custom handler
    // test route groups
    // test url generation

}