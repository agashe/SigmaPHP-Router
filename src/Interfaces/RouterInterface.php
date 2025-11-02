<?php

namespace SigmaPHP\Router\Interfaces;

/**
 * Router Interface
 */
interface RouterInterface
{
    /**
     * Run the router.
     * 
     * @return void
     */
    public function run();

    /**
     * Generate URL from route's name.
     * 
     * @param string $routeName
     * @param array $parameters
     * @return string
     */
    public function url($routeName, $parameters);

    /**
     * Set page not found handler.
     * 
     * @param string $handler 
     * @return void
     */
    public function setPageNotFoundHandler($handler);

    /**
     * Set actions runner.
     * 
     * @param string $runner
     * @param array $parameters
     * @return void
     */
    public function setActionRunner($runner, $parameters);
    
    /**
     * Get the base URL.
     * 
     * @return string
    */
    public function getBaseUrl();
    
    /**
     * Enable HTTP method override.
     * 
     * This only works for POST requests through HTML forms
     * by adding the _method hidden input field.
     * 
     * @return void
    */
    public function enableHttpMethodOverride();
}