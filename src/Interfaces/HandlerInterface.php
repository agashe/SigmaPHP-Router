<?php

namespace SigmaPHP\Router\Interfaces;

/**
 * Handler Interface
 */
interface HandlerInterface
{
    /**
     * Handle an action triggered by the Router.
     *
     * @return void
     */
    public function handle();
}
