<?php

namespace SigmaPHP\Router\Interfaces;

/**
 * Runner Interface
 */
interface RunnerInterface
{
    /**
     * Execute the route's action.
     * 
     * @param array $route
     * @return void
     */
    public function execute($route);
}