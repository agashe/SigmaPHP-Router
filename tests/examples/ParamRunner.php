<?php

namespace SigmaPHP\Router\Tests\Examples;

use SigmaPHP\Router\Interfaces\RunnerInterface;

/**
 * Example action runner with constructor that require parameters 
 * to use in router testing
 */
class ParamRunner implements RunnerInterface
{
    private $data;

    public function __construct($data) {
        $this->data = $data;
    }

    public function execute($route)
    {
        // In this custom runner , we add custom log message
        // before the execution
        echo "Log {$this->data}: "; 

        if (!isset($route['controller']) || empty($route['controller'])) {
            call_user_func($route['action'],...$route['parameters']);
        } else {
            $controller = new $route['controller']();
            $controller->{$route['action']}(...$route['parameters']);
        }  
    }
}