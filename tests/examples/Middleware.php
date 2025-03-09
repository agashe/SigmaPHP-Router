<?php

namespace SigmaPHP\Router\Tests\Examples;

/**
 * Example middleware to use in router testing
 */
class Middleware
{
    public function handler()
    {
        echo 'Middleware is working.';
    }
}