<?php

namespace SigmaPHP\Router;

use SigmaPHP\Router\Interfaces\RouterInterface;
use SigmaPHP\Router\Exceptions\RouteNotFoundException;
use SigmaPHP\Router\Exceptions\InvalidArgumentException;
use SigmaPHP\Router\Exceptions\DuplicatedRoutesException;
use SigmaPHP\Router\Exceptions\ActionIsNotDefinedException;
use SigmaPHP\Router\Exceptions\DuplicatedRouteNamesException;

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

        // set base path
        $this->host = $host;

        // set page not found handler to default
        $this->pageNotFoundHandler = null;

        // load the routes and process
        $this->routes = $this->load($routes);
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

                $routes[] = $route;
            }
        }

        // handle optional parameters and check for duplicated routes !!
        foreach ($routes as $key => $route) {
            $route['path'] = trim($route['path'], '/');

            // check route's name , and if empty use the its array key as name
            if (!isset($route['name']) || empty($route['name'])) {
                $route['name'] = $key;
            }

            // check for duplicated routes
            if (!isset($route['optional'])) {
                $similarRouteId = array_search(
                    $route['path'],
                    array_column($allRoutes, 'path')
                );
    
                $similarRouteMethod = false;
                
                if (
                    ($similarRouteId !== false) && (
                        ((!isset($route['method']) || 
                            empty($route['method'])) &&
                        (!isset($routes[$similarRouteId]['method']) || 
                            empty($routes[$similarRouteId]['method']))) ||
                        ($routes[$similarRouteId]['method'] == $route['method'])
                    )
                ) {
                    $similarRouteMethod = true;
                }
    
                if ($similarRouteMethod == true) {
                    throw new DuplicatedRoutesException(
                        "Route [{$route['path']}] is defined multiple times"
                    );
                }
    
                if (in_array($route['name'], array_column($allRoutes, 'name')))
                {
                    throw new DuplicatedRouteNamesException(
                        "Route [{$route['name']}] is defined multiple times"
                    );
                }            
            }

            // validate route methods
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

            if (strpos($route['path'], '?}') !== false) {
                // we save 2 copy of the route , one with optional parameter
                // and one without the parameter , then we use the flag 
                // "optional" to make sure that route won't be considered as
                // duplicated !

                $allRoutes[] = $route;

                $pathPrepared = preg_replace(
                    '~\{[^{}]*\?\}~',
                    '',
                    $route['path']
                );

                $route['path'] = trim($pathPrepared, '/');
                $route['optional'] = true;
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
                    } else if (strpos(
                        $route['path'], '{' . $key . '}') !== false
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
            
            // also notice this condition is to handle the single
            // parameter path "/{something}" e.g
            if (substr($pathPrepared, -1) == '}') {
                $pathPrepared = str_replace('}', '', $pathPrepared);
            }
            
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
            if (($route['name'] == $routeName) && !isset($route['optional'])) {
                $matchedRoute = $route;
                $path = $route['path'];
                break;
            }
        }

        if (empty($path)) {
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

        return (!empty($_SERVER['HTTPS']) ? 'https://' : 'http://') .
            $_SERVER['HTTP_HOST'] .
            (!empty($this->host) ? '/' . trim($this->host, '/') . '/' : '/') .
            rtrim($path, '/');
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
        if (
            !isset($matchedRoute['controller']) ||
            empty($matchedRoute['controller'])
        ) {
            call_user_func(
                $matchedRoute['action'],
                ...$matchedRoute['parameters']
            );
        } else {
            $controller = new $matchedRoute['controller']();

            $controller->{$matchedRoute['action']}(
                ...$matchedRoute['parameters']
            );
        }
    }
}
