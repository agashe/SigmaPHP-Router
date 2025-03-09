<?php

namespace SigmaPHP\Router\Tests\Examples;

/**
 * Example controller to use in router testing
 */
class SingleActionController
{
    public function __invoke()
    {
        echo 'Single Action Controller';
    }
}