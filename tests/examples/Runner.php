<?php

namespace SigmaPHP\Router\Tests\Examples;

use SigmaPHP\Router\Interfaces\RunnerInterface;

/**
 * Example action runner to use in router testing
 */
class Runner implements RunnerInterface
{
    public function execute($route)
    {
        // In this custom runner , we add custom log message
        // before the execution
        echo 'Log : '; 

        if (!isset($route['controller']) || empty($route['controller'])) {
            call_user_func($route['action'],...$route['parameters']);
        } else {
            $controller = new $route['controller']();
            $controller->{$route['action']}(...$route['parameters']);
        }  
    }
}