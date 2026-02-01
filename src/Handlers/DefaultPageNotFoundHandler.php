<?php

namespace SigmaPHP\Router\Handlers;

use SigmaPHP\Router\Interfaces\PageNotFoundHandlerInterface;

/**
 * Default Page Not Found Handler
 */
class DefaultPageNotFoundHandler implements PageNotFoundHandlerInterface
{
    /**
     * Handle an action triggered by the Router.
     *
     * @return void
     */
    public function handle()
    {
        http_response_code(404);
        echo "404 , The Requested URL Was Not Found";
    }
}
