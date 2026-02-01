<?php

namespace SigmaPHP\Router\Interfaces;

/**
 * Page Not Found Handler Interface
 */
interface PageNotFoundHandlerInterface extends HandlerInterface
{
    /**
     * Handle an action triggered by the Router.
     *
     * @return void
     */
    public function handle();
}
