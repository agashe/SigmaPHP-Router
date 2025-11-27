<?php

namespace SigmaPHP\Router;

use SigmaPHP\Router\Interfaces\RouterInterface;
use SigmaPHP\Router\Interfaces\RunnerInterface;
use SigmaPHP\Router\Exceptions\RouteNotFoundException;
use SigmaPHP\Router\Exceptions\InvalidArgumentException;
use SigmaPHP\Router\Exceptions\DuplicatedRoutesException;
use SigmaPHP\Router\Exceptions\ActionIsNotDefinedException;
use SigmaPHP\Router\Exceptions\DuplicatedRouteNamesException;
use SigmaPHP\Router\Exceptions\ControllerNotFoundException;
use SigmaPHP\Router\Runners\DefaultRunner;

/**
 * Router
 */
class Router implements RouterInterface
{
    /**
     * @var array $routes
     */
    private $routes;

    /**
     * @var string $host
     */
    private $host;

    /**
     * @var string $pageNotFoundHandler
     */
    private $pageNotFoundHandler;
    
    /**
     * @var RunnerInterface $actionRunner
     */
    private $actionRunner;

    /**
     * @var bool $httpMethodOverride
     */
    private $httpMethodOverride;

    /**
     * @var array $httpMethods
     */
    private $httpMethods = [
        'get'    , 'post'   , 'put',
        'patch'  , 'delete' , 'head',
        'connect', 'options', 'trace',
    ];

    /**
     * Router Constructor
     * 
     * @param array $routes
     * @param string $host
     */
    public function __construct($routes, $host = null)
    {
        if (!is_array($routes) || empty($routes)) {
            throw new InvalidArgumentException('Routes can\'t be empty');
        }

        // set base path if provided , otherwise load detect it automatically
        $this->host = ($host == null) ? $this->detectBasePath() : $host;

        // set page not found handler to null (to trigger the default handler)
        $this->pageNotFoundHandler = null;

        // set default action runner
        $this->actionRunner = new DefaultRunner();

        // load the routes and process
        $this->routes = $this->load($routes);

        // set default HTTP method override status
        $this->httpMethodOverride = false;
    }

    /**
     * Process routes.
     * 
     * @param array $routes
     * @return array
     */
    private function load($routes)
    {
        // we start by filtering all routes , and separate the route groups
        // then we combine them later to the routes array , after that
        // we can process the whole routes array    
        $allRoutes = [];

        $routeGroups = array_filter($routes, function ($route) {
            return (isset($route['group']) && !empty($route['group']));
        });

        $routes = array_filter($routes, function ($route) {
            return !isset($route['group']);
        });

        foreach ($routeGroups as $routeGroup) {
            if (!isset($routeGroup['routes']) || empty($routeGroup['routes'])) {
                throw new InvalidArgumentException(
                    "Routes can't be empty for group [{$routeGroup['group']}]"
                );
            }

            foreach ($routeGroup['routes'] as $key => $route) {
                $route['path'] = trim($route['path'], '/');

                // add middlewares to the group routes , if was provided
                if (isset($routeGroup['middlewares']) && 
                    !empty($routeGroup['middlewares'])
                ) {

                    $route['middlewares'] = isset($route['middlewares']) ?
                        array_merge(
                            $route['middlewares'],
                            $routeGroup['middlewares']
                        ) : $routeGroup['middlewares'];
                }

                // add prefix to the group routes , if was provided
                if (isset($routeGroup['prefix']) && 
                    !empty($routeGroup['prefix'])
                ) {
                    $routeGroup['prefix'] = trim($routeGroup['prefix'], '/');
                    $route['path'] = $routeGroup['prefix'] . 
                        '/' . $route['path'];
                }

                // add group name to its routes
                // Please Note : if the route has no name , then we use the
                // item key as a name
                if (isset($route['name']) && !empty($route['name'])) {
                    $route['name'] = $routeGroup['group'] .
                        '.' . $route['name'];
                } else {
                    $route['name'] = $routeGroup['group'] . '.' . $key;
                }

                // add controller to the group routes , if was provided and the
                // route doesn't already have a controller defined
                if (isset($routeGroup['controller']) && 
                    !empty($routeGroup['controller']) &&
                    !isset($route['controller']) &&
                    empty($route['controller'])
                ) {
                    if (!class_exists($routeGroup['controller'])) {
                        throw new ControllerNotFoundException("
                            The controller {$routeGroup['controller']} is not 
                            found
                        ");
                    }

                    $route['controller'] = $routeGroup['controller'];
                }

                $routes[] = $route;
            }
        }

        // handle optional parameters
        $tempRoutes = $routes;
        foreach ($tempRoutes as $key => $route) {
            if (strpos($route['path'], '?}') !== false) {
                // we save 2 copy of the route , both with optional parameter
                // then we use the flag "optional" to make sure that route won't
                // be considered as duplicated !
                $route['path'] = trim($route['path'], '/');

                // the new route , without the optional parameter
                $pathPrepared = preg_replace(
                    '~\{[^{}]*\?\}~',
                    '',
                    $route['path']
                );

                $route['path'] = trim($pathPrepared, '/');
                $route['optional'] = true;
                $tempRoutes[] = $route;

                // the old route as it is
                $tempRoutes[$key]['path'] = trim(
                    $tempRoutes[$key]['path'], '/');
                $tempRoutes[$key]['optional'] = true;
            }
        }

        $routes = $tempRoutes;
        
        // validate route methods
        foreach ($routes as $key => $route) {
            if (isset($route['method'])) {
                $route['method'] = trim($route['method'], ',');

                $route['method'] = ($route['method'] == 'any') ?
                    $this->httpMethods :
                    explode(',', strtolower($route['method']));

                if (count(array_intersect($this->httpMethods, $route['method']))
                    != count($route['method'])
                ) {
                    throw new InvalidArgumentException(
                        "Invalid HTTP methods for route {$route['path']}"
                    );
                }
            } else {
                $route['method'] = ['get'];
            }

            $routes[$key] = $route;
        }

        // check for duplicated routes' names 
        $nonOptionalRoutes = array_filter($routes, function ($route) {
            return !isset($route['optional']);
        });

        $duplicateRouteNames = array_keys(array_filter(
            array_count_values(array_column($nonOptionalRoutes, 'name')), 
            function ($count) {
                return $count > 1;
            }
        ));

        if (!empty($duplicateRouteNames)) {
            throw new DuplicatedRouteNamesException(
                "Route [{$duplicateRouteNames[0]}] is defined multiple times"
            );
        }

        // check for duplicated routes  
        foreach ($routes as $key => $route) {
            $route['path'] = trim($route['path'], '/');

            // check route's name , and if empty use its array key as name
            if (!isset($route['name']) || empty($route['name'])) {
                $route['name'] = $key;
            }

            foreach ($routes as $k => $v) {
                $v['path'] = trim($v['path'], '/');
                
                if ($k == $key) {
                    continue;
                }
                
                if ($v['path'] == $route['path'] &&
                    $v['method'] == $route['method']
                ) {
                    $pathStr = $route['path'] ?: '/';

                    throw new DuplicatedRoutesException(
                        "Route [{$pathStr}] is defined multiple times"
                    );
                }
            }
            
            $allRoutes[] = $route;
        }

        return $allRoutes;
    }

    /**
     * Check if route exists.
     * 
     * @param string $method
     * @param string $path
     * @return array
     */
    private function match($method, $path)
    {
        $path = trim($path, '/');

        foreach ($this->routes as $route) {
            $pathPrepared = '';

            // handle validation
            if (isset($route['validation']) && !empty($route['validation'])) {
                foreach ($route['validation'] as $key => $value) {
                    if ((strpos($route['path'], '{' . $key . '?}') !== false)) {
                        $route['path'] = str_replace(
                            '{' . $key . '?}',
                            "($value)",
                            $route['path']
                        );
                    }
                    else if (
                        strpos($route['path'], '{' . $key . '}') !== false
                    ) {
                        $route['path'] = str_replace(
                            '{' . $key . '}',
                            "($value)",
                            $route['path']
                        );
                    }
                }
            }

            // the following regex pattern simply match the
            // variables and replace them with simpler pattern
            // example : /users/{id}/orders/{order_id}
            // payload : /users/1000/orders/1234567890
            // result parameters => [1000, 1234567890]
            $pathPrepared = preg_replace(
                '~^\{[^{}]*|^[^{}]*\}$|\{[^{}]*\}~',
                '([^\/]+)',
                $route['path']
            );
            
            // also notice this condition is to handle any additional '}'
            // that could be found !
            $pathPrepared = str_replace('}', '', $pathPrepared);

            if (
                preg_match('~^' . $pathPrepared . '$~', $path, $parameters) &&
                in_array(strtolower($method), $route['method'])
            ) {
                unset($parameters[0]);
                $parameters = array_values($parameters);

                return $route + ['parameters' => $parameters];
            }
        }

        return [];
    }

    /**
     * Default page not found handler.
     * 
     * @return void
     */
    private function defaultPageNotFoundHandler()
    {
        http_response_code(404);
        echo "404 , The Requested URL Was Not Found";
    }

    /**
     * Handle query parameters.
     * 
     * @param string $uri
     * @return string
     */
    private function handleQueryParameters($uri)
    {
        if (strpos($uri, '?') === false) {
            return $uri;
        }
        
        // all before "?" is the original uri , where all after are the params
        $extractedParts = explode('?', $uri);
        
        // process query parameters and save them into $_GET
        foreach (explode('&', $extractedParts[1]) as $parameter) {
            $keyVal = explode('=', $parameter);
            $_GET[$keyVal[0]] = $keyVal[1];
        }

        return $extractedParts[0];
    }

    /**
     * Get the base path of the server.
     * 
     * This is extremely important if you're running your app from Apache or 
     * Nginx servers directly , without the PHP built in server.
     * 
     * @return string
     */
    private function detectBasePath()
    {
        // we exclude the script name from (SCRIPT_NAME) and whatever remaining
        // that's our base path !
        return preg_replace('~\/[^\/]+\.php~', '', $_SERVER['SCRIPT_NAME']);
    }

    /**
     * Set page not found handler.
     * 
     * @param string|array $handler 
     * @return void
     */
    public function setPageNotFoundHandler($handler)
    {
        $this->pageNotFoundHandler = $handler;
    }
    
    /**
     * Set actions runner.
     * 
     * @param string $runner
     * @param array $parameters
     * @return void
     */
    public function setActionRunner($runner, $parameters = [])
    {
        // the runner should be a valid class , and MUST implement
        // the runner interface
        if (!is_string($runner) || empty($runner) || !class_exists($runner)) {
            throw new InvalidArgumentException(
                "Invalid runner , actions " .
                "runner should be a valid class !"
            );
        }

        $interfaces = class_implements($runner);

        if (empty($interfaces) ||
            !in_array(RunnerInterface::class, $interfaces)
        ) {
            throw new InvalidArgumentException(
                "Invalid runner [{$runner}] , action runner " . 
                "MUST implement RunnerInterface !"
            );
        }

        // if the parameters are not an array throw exception
        if (!is_array($parameters)) {
            throw new InvalidArgumentException(
                "Invalid parameters for action runner [{$runner}] " .
                "parameters can only be array !"
            );
        }
        
        $this->actionRunner = new $runner(...$parameters);
    }

    /**
     * Get the base URL.
     * 
     * @return string
    */
    public function getBaseUrl()
    {
        return (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') .
            $_SERVER['HTTP_HOST'] .
            (!empty($this->host) ? '/' . trim($this->host, '/') . '/' : '/');
    }

    /**
     * Enable HTTP method override.
     * 
     * This only works for POST requests through HTML forms
     * by adding the _method hidden input field.
     * 
     * @return void
    */
    public function enableHttpMethodOverride()
    {
        $this->httpMethodOverride = true;
    }

    /**
     * Generate URL from route's name.
     * 
     * @param string $routeName
     * @param array $parameters
     * @return string
     */
    public function url($routeName, $parameters = [])
    {
        $path = '';
        $matchedRoute = [];

        foreach ($this->routes as $route) {
            if ($route['name'] == $routeName) {
                $matchedRoute = $route;
                $path = $route['path'];
                break;
            }
        }

        if (empty($matchedRoute)) {
            throw new RouteNotFoundException(
                "Route [{$routeName}] is not found"
            );
        }

        // handle optional parameters , if no parameters were passed
        if ((strpos($path, '?}') !== false) && empty($parameters)) {
            $path = preg_replace(
                '~\{[^{}]*\?\}~',
                '',
                $path
            );
        }

        // set parameter values
        foreach ($parameters as $key => $value) {
            if ((strpos($path, '{' . $key . '?}') !== false)) {
                $path = str_replace('{' . $key . '?}', $value, $path);
            } else {
                $path = str_replace('{' . $key . '}', $value, $path);
            }
        }

        // we check if the current path still has "}" symbol then
        // there are some missing parameters , throw exception 
        if (strpos($path, '}') !== false) {
            throw new InvalidArgumentException(
                "Missing parameters for Route {$matchedRoute['name']}"
            );
        }

        return $this->getBaseUrl() . rtrim($path, '/');
    }
    
    /**
     * Run the router.
     * 
     * @return void
     */
    public function run()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        // In case the router was used in sub-directory
        // we remove the host (sub-directory) from the URI
        if (!empty($this->host)) {
            $uri = str_replace($this->host, '', $uri);
        }

        // handle query parameters
        $uri = $this->handleQueryParameters($uri);

        // handle http method override , only for POST requests
        // while _method is set
        if ($this->httpMethodOverride) {
            if (strtolower($method) == 'post' && isset($_POST['_method'])) {
                if (!in_array(strtolower($_POST['_method']), $this->httpMethods)
                ) {
                    throw new InvalidArgumentException(
                        "Invalid HTTP method {$_POST['_method']} !"
                    );
                }

                $method = $_POST['_method'];
                $_SERVER['REQUEST_METHOD'] = strtoupper($method);
            }
        }

        // match the route
        $matchedRoute = $this->match($method, $uri);

        // handle page not found case
        if (empty($matchedRoute)) {
            if (!empty($this->pageNotFoundHandler)) {
                if (is_string($this->pageNotFoundHandler)) {
                    call_user_func($this->pageNotFoundHandler);
                } 
                else if (is_array($this->pageNotFoundHandler) && 
                    (count($this->pageNotFoundHandler) == 2)
                ) {
                    $pageNotFoundHandlerInstance = new 
                        $this->pageNotFoundHandler[0]();
                    
                    $pageNotFoundHandlerInstance->
                        {$this->pageNotFoundHandler[1]}();
                }
                else {
                    throw new InvalidArgumentException(
                        "Invalid pageNotFoundHandler"
                    );
                }
            } else {
                $this->defaultPageNotFoundHandler();
            }

            return;
        }

        // check if the route has a valid action
        if ((!isset($matchedRoute['action']) || empty($matchedRoute['action']))
            &&
            (!isset($matchedRoute['controller']) || 
            empty($matchedRoute['controller']))
        ) {
            throw new ActionIsNotDefinedException(
                "Route {$matchedRoute['path']} doesn't have valid action"
            );
        }
        
        // handle single action controller , we assume that only "__invoke"
        // magic method exists in the controller , so we call it
        if ((!isset($matchedRoute['action']) || empty($matchedRoute['action']))
            &&
            (isset($matchedRoute['controller']) && 
            !empty($matchedRoute['controller']))
        ) {
            $matchedRoute['action'] = '__invoke';
        }

        // execute route's middlewares
        if (isset($matchedRoute['middlewares']) &&
            is_array($matchedRoute['middlewares']) &&
            !empty($matchedRoute['middlewares'])
        ) {
            foreach ($matchedRoute['middlewares'] as $middleware) {
                if (is_string($middleware)) {
                    call_user_func($middleware);
                } 
                else if (is_array($middleware) && (count($middleware) == 2)) {
                    $middlewareInstance = new $middleware[0]();
                    $middlewareInstance->{$middleware[1]}();
                }
                else {
                    throw new InvalidArgumentException(
                        "Invalid middlewares for route {$matchedRoute['path']}"
                    );
                }
            }
        }

        // execute route's action
        $this->actionRunner->execute($matchedRoute);     
    }
}
