<?php

namespace SigmaPHP\Router;

use SigmaPHP\Router\Exceptions\RouteNotFoundException;
use SigmaPHP\Router\Exceptions\InvalidArgumentException;
use SigmaPHP\Router\Exceptions\DuplicatedRoutesException;
use SigmaPHP\Router\Exceptions\ActionIsNotDefinedException;
use SigmaPHP\Router\Exceptions\DuplicatedRouteNamesException;

/**
 * Router
 */
class Router
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
     * Process routes
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
            $routeGroup['path'] = trim($routeGroup['path'], '/');

            foreach ($routeGroup['routes'] as $route) {
                $route['path'] = trim($route['path'], '/');

                $route['middlewares'] = isset($route['middlewares']) ?
                    array_merge(
                        $route['middlewares'],
                        $routeGroup['middlewares']
                    ) : $routeGroup['middlewares'];

                $route['path'] = $routeGroup['path'] . '/' . $route['path'];
                $route['name'] = $routeGroup['group'] . '.' . $route['name'];

                $routes[] = $route;
            }
        }

        // handle optional parameters and check for duplicated routes !!
        foreach ($routes as $route) {
            $route['path'] = trim($route['path'], '/');

            if (
                array_search(
                    $route['path'],
                    array_column($allRoutes, 'path')
                ) && !isset($route['optional'])
            ) {
                throw new DuplicatedRoutesException(
                    "Route [{$route['path']}] is defined multiple times"
                );
            }

            if (
                in_array($route['name'], array_column($allRoutes, 'name')) &&
                !isset($route['optional'])
            ) {
                throw new DuplicatedRouteNamesException(
                    "Route [{$route['name']}] is defined multiple times"
                );
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
     * Check if route exists
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
                    } else if (strpos($route['path'], '{' . $key . '}') !== false) {
                        $route['path'] = str_replace(
                            '{' . $key . '}',
                            "($value)",
                            $route['path']
                        );
                    }
                }
            }

            $pathPrepared = preg_replace(
                '~^\{[^{}]*|^[^{}]*\}$|\{[^{}]*\}~',
                '([^\/]*)',
                $route['path']
            );

            $routeMethods = ($route['method'] == 'any') ?
                [strtolower($method)] :
                explode(',', strtolower($route['method']));

            if (
                preg_match('~^' . $pathPrepared . '$~', $path, $parameters) &&
                in_array(strtolower($method), $routeMethods)
            ) {
                unset($parameters[0]);
                $parameters = array_values($parameters);

                return $route + ['parameters' => $parameters];
            }
        }

        return [];
    }

    /**
     * Default page not found handler
     * 
     * @return void
     */
    private function defaultPageNotFoundHandler()
    {
        http_response_code(404);
        echo "404 , The Requested URL Is Not Found";
        die();
    }

    /**
     * Set page not found handler
     * 
     * @param string $handler 
     * @return void
     */
    public function setPageNotFoundHandler($handler)
    {
        $this->pageNotFoundHandler = $handler;
    }

    /**
     * Generate URL from route's name
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
     * Run the router
     * 
     * @return void
     */
    public function run()
    {
        $uri = $_SERVER['REQUEST_URI'];
        $method = $_SERVER['REQUEST_METHOD'];

        // In case the router was used in sub-directory
        // we remove the host (sub-directory) from the URI
        $cleanUri = str_replace($this->host, '', $uri);

        $matchedRoute = $this->match($method, $cleanUri);

        if (empty($matchedRoute)) {
            if (!empty($this->pageNotFoundHandler)) {
                call_user_func($this->pageNotFoundHandler);
            } else {
                $this->defaultPageNotFoundHandler();
            }
        }

        if (
            !is_string($matchedRoute['action']) ||
            empty($matchedRoute['action'])
        ) {
            throw new ActionIsNotDefinedException(
                "Route {$matchedRoute['path']} doesn't have valid action"
            );
        }

        if (
            isset($matchedRoute['middlewares']) &&
            is_array($matchedRoute['middlewares']) &&
            !empty($matchedRoute['middlewares'])
        ) {
            foreach ($matchedRoute['middlewares'] as $middleware) {
                $middlewareInstance = new $middleware[0]();
                $middlewareInstance->{$middleware[1]}();
            }
        }

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
