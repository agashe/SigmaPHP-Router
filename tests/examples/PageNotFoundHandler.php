<?php

namespace SigmaPHP\Router\Tests\Examples;

use SigmaPHP\Router\Interfaces\PageNotFoundHandlerInterface;

/**
 * Example page not found handler to use in router testing
 */
class PageNotFoundHandler implements PageNotFoundHandlerInterface
{
    public function handle()
    {
        echo 'PageNotFoundHandler is working.';
    }
}
