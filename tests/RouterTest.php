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
                'method' => 'get',
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
                'path' => '/test5/controller',
                'method' => 'get',
                'controller' => ExampleController::class,
                'action' => 'index'
            ],
            [
                'name' => 'test6',
                'path' => '/test6/middleware',
                'method' => 'get',
                'middlewares' => [
                    [ExampleMiddleware::class, 'handler'],
                ],
                'controller' => ExampleController::class,
                'action' => 'index'
            ],
            [
                'name' => 'test7',
                'path' => '/test7/validation/{data}',
                'method' => 'get',
                'action' => 'route_handler_d',
                'validation' => [
                    'data' => '[0-9]+'
                ]
            ],
            [
                'name' => 'test8',
                'path' => '/test8/any-method/{data}',
                'method' => 'any',
                'action' => 'route_handler_d',
            ],
            [
                'group' => 'test_group',
                'prefix' => 'test-group/',
                'middlewares' => [
                    [ExampleMiddleware::class, 'handler'],
                ],
                'routes' => [
                    [
                        'name' => 'test9',
                        'path' => '/test9',
                        'method' => 'post',
                        'controller' => ExampleController::class,
                        'action' => 'index'
                    ],
                ]
            ],
            [
                'name' => 'test10',
                'path' => '/test10/default-method',
                'action' => 'route_handler_a'
            ],
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

    /**
     * Test router can call actions from controller.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanCallActionsFromController()
    {
        $_SERVER['REQUEST_URI'] = '/test5/controller';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("Example Controller Index Method");
    }
    
    /**
     * Test router run middlewares before action execution.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterRunMiddlewaresBeforeActionExecution()
    {
        $_SERVER['REQUEST_URI'] = '/test6/middleware';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Middleware is working.Example Controller Index Method"
        );
    }

    /**
     * Test router can validate parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanValidateParameters()
    {
        $_SERVER['REQUEST_URI'] = '/test7/validation/wrong';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "404 , The Requested URL Was Not Found"
        );
    }

    /**
     * Test route can use any HTTP method.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouteCanUseAnyHTTPMethod()
    {
        $_SERVER['REQUEST_URI'] = '/test8/any-method/data';
        $_SERVER['REQUEST_METHOD'] = 'PUT';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("data");
    }

    /**
     * Test router will through exception if the action doesn't exist.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheActionDoesNotExist()
    {
        $this->expectException(ActionIsNotDefinedException::class);

        $duplicatedRoutes = array_merge($this->routes, [
            [
                'name' => 'testNoAction',
                'path' => '/no-action',
                'method' => 'get',
            ]
        ]);  
        
        $router = new Router($duplicatedRoutes);

        $_SERVER['REQUEST_URI'] = '/no-action';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // run the router
        $router->run();
    }

    /**
     * Test router can handle page not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanHandlePageNotFound()
    {
        $_SERVER['REQUEST_URI'] = '/page-not-found';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "404 , The Requested URL Was Not Found"
        );
    }

    /**
     * Test router can use custom handler for page not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanUseCustomHandlerForPageNotFound()
    {
        $_SERVER['REQUEST_URI'] = '/page-not-found';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // set custom page not found handler
        $router->setPageNotFoundHandler('route_not_found_handler');

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "This is a custom page not found handler"
        );
    }

    /**
     * Test route groups.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouteGroups()
    {
        $_SERVER['REQUEST_URI'] = '/test-group/test9';
        $_SERVER['REQUEST_METHOD'] = 'POST';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Middleware is working.Example Controller Index Method"
        );
    }

    /**
     * Test router can generate url from route name.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanGenerateUrlFromRouteName()
    {
        $_SERVER['HTTPS'] = null;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // create new router instance
        $router = new Router($this->routes);

        // assert result
        $this->assertEquals(
            $router->URL('test1'),
            'http://localhost/test1/static'
        );
    }

    /**
     * Test router will through exception if route name doesn't exist for url 
     * generation.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfRouteNameDoesNotExist()
    {
        $this->expectException(RouteNotFoundException::class);

        $_SERVER['HTTPS'] = null;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // create new router instance
        $router = new Router($this->routes);

        // generate route
        $router->url('unknown_route');
    }

    /**
     * Test router will through exception if required parameters weren't passed
     * when creating url from route name.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfParametersWereNotPassed()
    {
        $this->expectException(InvalidArgumentException::class);

        $_SERVER['HTTPS'] = null;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // create new router instance
        $router = new Router($this->routes);

        // generate route
        $router->url('test2');
    }

    /**
     * Test route group prefix is added to routes name.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouteGroupPrefixIsAddedToRoutesName()
    {
        $_SERVER['HTTPS'] = null;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // create new router instance
        $router = new Router($this->routes);

        // assert result
        $this->assertEquals(
            $router->URL('test_group.test9'),
            'http://localhost/test-group/test9'
        );
    }

    /**
     * Test router will through exception if the route methods are invalid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheRouteMethodsAreInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $routes = array_merge($this->routes, [
            [
                'name' => 'testX',
                'path' => '/test-invalid-http-method',
                'method' => 'my-custom-method',
                'action' => 'route_handler_a',
            ]
        ]);  
        
        $router = new Router($routes);
    }

    /**
     * Test router will set the default HTTP method to GET if was not specified.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillSetDefaultHTTPMethodToGETIfWasNotSpecified()
    {
        $_SERVER['REQUEST_URI'] = '/test10/default-method';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("some data");
    }
}