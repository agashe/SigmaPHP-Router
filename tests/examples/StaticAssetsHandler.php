<?php

namespace SigmaPHP\Router\Tests\Examples;

use SigmaPHP\Router\Interfaces\StaticAssetsHandlerInterface;

/**
 * Example Static Assets Handler
 */
class StaticAssetsHandler implements StaticAssetsHandlerInterface
{
    /**
     * Handle an action triggered by the Router.
     *
     * @param string $resourcePath
     * @return void
     */
    public function handle($resourcePath)
    {
        echo "Custom Static Assets Handler !\n";
    }
}
