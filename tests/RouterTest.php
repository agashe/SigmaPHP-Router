<?php

use SigmaPHP\Router\Router;
use PHPUnit\Framework\TestCase;
use SigmaPHP\Router\Exceptions\RouteNotFoundException;
use SigmaPHP\Router\Exceptions\InvalidArgumentException;
use SigmaPHP\Router\Exceptions\DuplicatedRoutesException;
use SigmaPHP\Router\Exceptions\ActionIsNotDefinedException;
use SigmaPHP\Router\Exceptions\ActionNotFoundException;
use SigmaPHP\Router\Exceptions\ControllerNotFoundException;
use SigmaPHP\Router\Exceptions\DuplicatedRouteNamesException;
use SigmaPHP\Router\Tests\Examples\Runner as ExampleRunner;
use SigmaPHP\Router\Tests\Examples\ParamRunner as ExampleParamRunner;
use SigmaPHP\Router\Tests\Examples\InvalidRunner as ExampleInvalidRunner;
use SigmaPHP\Router\Tests\Examples\Controller as ExampleController;
use SigmaPHP\Router\Tests\Examples\PageNotFoundHandler
    as ExamplePageNotFoundHandler;

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
        $this->routes = require('routes.php');

        // set SCRIPT_NAME for all test cases
        $_SERVER['SCRIPT_NAME'] = '/index.php';
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
     * Test router can parse start/end parameters URLs.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanParseStartEndParametersURLs()
    {
        $_SERVER['REQUEST_URI'] = '/s1/test15/s2/test/s3';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("s1 , s2 and s3 were received");
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
     * Test router will through exception if root route path is duplicated.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfRootRoutePathIsDuplicated()
    {
        $this->expectException(DuplicatedRoutesException::class);

        // create new router instance
        $router = new Router([
            [
                'name' => 'test_root_optional',
                'path' => '/{name?}',
                'action' => 'route_handler_d'
            ],
            [
                'name' => 'test_root',
                'path' => '/',
                'action' => 'route_handler_a'
            ],
        ]);
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
     * Test router will through exception if the action is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheActionIsNotFound()
    {
        $this->expectException(ActionNotFoundException::class);

        $duplicatedRoutes = array_merge($this->routes, [
            [
                'name' => 'testActionNotFound',
                'path' => '/action-not-found-exception',
                'method' => 'get',
                'action' => 'not_found',
            ]
        ]);

        $router = new Router($duplicatedRoutes);

        $_SERVER['REQUEST_URI'] = '/action-not-found-exception';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // run the router
        $router->run();
    }

    /**
     * Test router will through exception if the controller is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheControllerIsNotFound()
    {
        $this->expectException(ControllerNotFoundException::class);

        $duplicatedRoutes = array_merge($this->routes, [
            [
                'name' => 'testControllerNotFound',
                'path' => '/controller-not-found-exception',
                'method' => 'get',
                'controller' => 'ControllerNotFound',
                'action' => 'myAction',
            ]
        ]);

        $router = new Router($duplicatedRoutes);

        $_SERVER['REQUEST_URI'] = '/controller-not-found-exception';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // run the router
        $router->run();
    }

    /**
     * Test router will through exception if the action is not found in the
     * controller.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfActionNotInController()
    {
        $this->expectException(ActionNotFoundException::class);

        $duplicatedRoutes = array_merge($this->routes, [
            [
                'name' => 'testActionNotFoundInController',
                'path' => '/action-not-found-in-controller',
                'method' => 'get',
                'controller' => ExampleController::class,
                'action' => 'myAction',
            ]
        ]);

        $router = new Router($duplicatedRoutes);

        $_SERVER['REQUEST_URI'] = '/action-not-found-in-controller';
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


    /**
     * Test single action controller.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testSingleActionController()
    {
        $_SERVER['REQUEST_URI'] = '/test11/single-action-controller';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("Single Action Controller");
    }

    /**
     * Test middlewares can be regular functions.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testMiddlewaresCanBeRegularFunctions()
    {
        $_SERVER['REQUEST_URI'] = '/test12/regular-function-middleware';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Middleware function.some data"
        );
    }

    /**
     * Test route can work without name.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouteCanWorkWithoutName()
    {
        $_SERVER['REQUEST_URI'] = '/test13/route-without-name';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "some data"
        );
    }

    /**
     * Test root route.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRootRoute()
    {
        $this->expectException(DuplicatedRoutesException::class);

        // create new router instance
        $router = new Router([
            [
                'name' => 'test_root_optional',
                'path' => '/{name?}',
                'action' => 'route_handler_d'
            ],
            [
                'name' => 'test_root',
                'path' => '/',
                'action' => 'route_handler_a'
            ],
        ]);
    }

    /**
     * Test root route with parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRootRouteWithOptionalParameters()
    {
        $_SERVER['REQUEST_URI'] = '/ahmed';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router([
            [
                'name' => 'test_root_optional',
                'path' => '/{name?}',
                'action' => 'route_handler_d'
            ]
        ]);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "ahmed"
        );
    }

    /**
     * Test root route with parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRootRouteWithParameters()
    {
        $_SERVER['REQUEST_URI'] = '/omar';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router([
            [
                'path' => '/test1',
                'action' => 'route_handler_a'
            ],
            [
                'name' => 'test_root_param',
                'path' => '/{name}',
                'action' => 'route_handler_d'
            ],
            [
                'path' => '/test2',
                'action' => 'route_handler_a'
            ]
        ]);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "omar"
        );
    }

    /**
     * Test routes group optional items (prefix & middlewares).
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRoutesGroupOptionalItems()
    {
        $_SERVER['REQUEST_URI'] = '/test14';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "some data"
        );
    }

    /**
     * Test router will through exception if the group routes are empty.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheGroupRoutesAreEmpty()
    {
        $this->expectException(InvalidArgumentException::class);

        $routes = array_merge($this->routes, [
            [
                'group' => 'test_group_optional',
            ],
        ]);

        $router = new Router($routes);
    }

    /**
     * Test router will through exception if the middlewares are invalid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheMiddlewaresAreInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $routes = array_merge($this->routes, [
            [
                'path' => '/test-invalid-middlewares',
                'middlewares' => [false],
                'action' => 'route_handler_a',
            ],
        ]);

        $_SERVER['REQUEST_URI'] = '/test-invalid-middlewares';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $router = new Router($routes);

        // run the router
        $router->run();
    }

    /**
     * Test custom handler for page not found can be a class.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testCustomHandlerForPageNotFoundCanBeAClass()
    {
        $_SERVER['REQUEST_URI'] = '/page-not-found';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set custom page not found handler
        $router->setPageNotFoundHandler([
            ExamplePageNotFoundHandler::class,
            'handler'
        ]);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "PageNotFoundHandler is working."
        );
    }

    /**
     * Test router will through exception if the page not found handler
     * is invalid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfInvalidPageNotFoundHandler()
    {
        $this->expectException(InvalidArgumentException::class);

        $_SERVER['REQUEST_URI'] = '/page-not-found';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set custom page not found handler
        $router->setPageNotFoundHandler([null]);

        // run the router
        $router->run();
    }

    /**
     * Test custom actions runner.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testCustomActionsRunner()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set custom action runner
        $router->setActionRunner(ExampleRunner::class);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Log : some data"
        );
    }

    /**
     * Test router will through exception if the actions runner is invalid.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterWillThroughExceptionIfTheActionsRunnerIsInvalid()
    {
        $this->expectException(InvalidArgumentException::class);

        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set non-class action runner
        $router->setActionRunner([null]);

        // run the router
        $router->run();
    }

    /**
     * Test router will through exception if the actions runner doesn't
     * implement the RunnerInterface.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testExceptionIfTheRunnerDoesNotImplementTheRunnerInterface()
    {
        $this->expectException(InvalidArgumentException::class);

        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set invalid actions runner
        $router->setActionRunner(ExampleInvalidRunner::class);

        // run the router
        $router->run();
    }

    /**
     * Test custom actions runner can accept parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testCustomActionsRunnerCanAcceptParameters()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set custom action runner
        $router->setActionRunner(ExampleParamRunner::class, ['message']);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Log message: some data"
        );
    }

    /**
     * Test router will through exception if the actions runner parameters
     * are not of type array.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterThroughExceptionIfRunnerParametersNotArray()
    {
        $this->expectException(InvalidArgumentException::class);

        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // set invalid parameters
        $router->setActionRunner(ExampleParamRunner::class, 123);

        // run the router
        $router->run();
    }

    /**
     * Test router can handle query parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanHandleQueryParameters()
    {
        $_SERVER['REQUEST_URI'] = '/test2/my-data?name=ahmed&age=15';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("my-data was received");

        // check that $_GET has the query parameters
        $this->assertEquals('ahmed', $_GET['name']);
        $this->assertEquals('15', $_GET['age']);
    }

    /**
     * Test get base url.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testGetBaseUrl()
    {
        $_SERVER['HTTPS'] = null;
        $_SERVER['HTTP_HOST'] = 'localhost';

        // create new router instance
        $router = new Router($this->routes);

        // assert result
        $this->assertEquals(
            $router->getBaseUrl(),
            'http://localhost'
        );
    }

    /**
     * Test routes group can share controllers.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRoutesGroupCanShareControllers()
    {
        $_SERVER['REQUEST_URI'] = '/test-group-controller/test16';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        $_SERVER['REQUEST_URI'] = '/test-group-controller/test18';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Example GroupController Home Method" .
            "Example GroupController About Method"
        );
    }

    /**
     * Test routes group controllers can be overwritten by routes.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRoutesGroupControllersCanBeOverwrittenByRoutes()
    {
        $_SERVER['REQUEST_URI'] = '/test-group-controller/test17';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "Example Controller Index Method"
        );
    }

    /**
     * Test routes group will through exception if the controller is not found.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRoutesGroupWillThroughExceptionIfControllerIsNotFound()
    {
        $this->expectException(ControllerNotFoundException::class);

        $routes = [
            [
                'group' => 'test_group_controller_not_found',
                'prefix' => 'test-group-controller-not-found',
                'controller' => 'ControllerNotFound',
                'routes' => [
                    [
                        'name' => 'testGroupControllerNotFound',
                        'path' => '/',
                        'method' => 'get',
                        'action' => 'myAction',
                    ],
                ]
            ]
        ];

        $router = new Router($routes);

        $_SERVER['REQUEST_URI'] = '/test-group-controller-not-found';
        $_SERVER['REQUEST_METHOD'] = 'GET';

        // run the router
        $router->run();
    }

    /**
     * Test router override HTTP methods.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterOverrideHttpMethods()
    {
        $_SERVER['REQUEST_URI'] = '/test19/http_method_override';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST['_method'] = 'PUT';

        // create new router instance
        $router = new Router($this->routes);

        // enable HTTP method override
        $router->enableHttpMethodOverride();

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("some data");
    }

    /**
     * Test router will throw exception if the provided method is invalid for
     * HTTP method override.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterThrowExceptionIfMethodIsInvalidForMethodOverride()
    {
        $this->expectException(InvalidArgumentException::class);

        $router = new Router($this->routes);

        $_SERVER['REQUEST_URI'] = '/test19/http_method_override';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_POST['_method'] = 'WEIRD';

        // enable HTTP method override
        $router->enableHttpMethodOverride();

        // run the router
        $router->run();
    }

    /**
     * Test HEAD HTTP method response.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testHeadHttpMethodResponse()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'HEAD';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString("");
    }

    /**
     * Test OPTIONS HTTP method response.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testOptionsHttpMethodResponse()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'OPTIONS';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "405 , The HTTP method you requested is not allowed\n" .
            "The allowed HTTP methods are : GET\n"
        );
    }

    /**
     * Test TRACE HTTP method response.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testTraceHttpMethodResponse()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'TRACE';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "405 , The HTTP method you requested is not allowed\n"
        );
    }

    /**
     * Test CONNECT HTTP method response.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testConnectHttpMethodResponse()
    {
        $_SERVER['REQUEST_URI'] = '/test1/static';
        $_SERVER['REQUEST_METHOD'] = 'CONNECT';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString(
            "405 , The HTTP method you requested is not allowed\n"
        );
    }

    /**
     * Test router can decode special characters in URL parameters.
     *
     * @runInSeparateProcess
     * @return void
     */
    public function testRouterCanDecodeSpecialCharactersInUrlParameters()
    {
        $_SERVER['REQUEST_URI'] =
            '/test20/special_chars/!@$%^&*()_+~%60;%22,.%3C%3E';

        $_SERVER['REQUEST_METHOD'] = 'GET';

        // create new router instance
        $router = new Router($this->routes);

        // run the router
        $router->run();

        // assert result
        $this->expectOutputString('!@$%^&*()_ ~`;",.<>');
    }
}
