<?php

namespace SigmaPHP\Router\Runners;

use SigmaPHP\Router\Interfaces\RunnerInterface;
use SigmaPHP\Router\Exceptions\ActionNotFoundException;
use SigmaPHP\Router\Exceptions\ControllerNotFoundException;

/**
 * Default Runner
 */
class DefaultRunner implements RunnerInterface
{
    /**
     * Execute the route's action.
     * 
     * @param array $route
     * @return void
     */
    public function execute($route)
    {
        if (!isset($route['controller']) || empty($route['controller'])) {
            if (!function_exists($route['action'])) {
                throw new ActionNotFoundException("
                    The action {$route['action']} is not found
                ");
            }
            
            call_user_func($route['action'],...$route['parameters']);
        } else {
            if (!class_exists($route['controller'])) {
                throw new ControllerNotFoundException("
                    The controller {$route['controller']} is not found
                ");
            }

            $controller = new $route['controller']();

            if (!method_exists($controller, $route['action'])) {
                throw new ActionNotFoundException("
                    The action {$route['action']} is not found in 
                    {$route['controller']} controller
                ");
            }

            $controller->{$route['action']}(...$route['parameters']);
        }
    }
}