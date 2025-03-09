<?php

namespace SigmaPHP\Router\Tests\Examples;

/**
 * Example page not found handler to use in router testing
 */
class PageNotFoundHandler
{
    public function handler()
    {
        echo 'PageNotFoundHandler is working.';
    }
}