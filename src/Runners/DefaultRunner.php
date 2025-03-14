<?php

namespace SigmaPHP\Router\Runners;

use SigmaPHP\Router\Interfaces\RunnerInterface;

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
            call_user_func($route['action'],...$route['parameters']);
        } else {
            $controller = new $route['controller']();
            $controller->{$route['action']}(...$route['parameters']);
        }
    }
}