<?php

namespace SigmaPHP\Router\Tests\Examples;

/**
 * Example controller to use in router testing
 */
class GroupController
{
    public function home()
    {
        echo 'Example GroupController Home Method';    
    }
    
    public function about()
    {
        echo 'Example GroupController About Method';    
    }
}