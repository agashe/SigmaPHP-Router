<?php

namespace SigmaPHP\Router\Interfaces;

/**
 * Static Assets Handler Interface
 */
interface StaticAssetsHandlerInterface
{
    /**
     * Handle an action triggered by the Router.
     *
     * @param string $resourcePath
     * @return void
     */
    public function handle($resourcePath);
}
