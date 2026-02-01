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
     * @param PageNotFoundHandlerInterface $handler
     * @return void
     */
    public function setPageNotFoundHandler($handler);

    /**
     * Set actions runner.
     *
     * @param RunnerInterface $runner
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

    /**
     * Set static assets route path.
     *
     * @param string $path
     * @return void
     */
    public function setStaticAssetsRoutePath($path);

    /**
     * Set static assets route handler.
     *
     * @param StaticAssetsHandlerInterface $handler
     * @return void
     */
    public function setStaticAssetsRouteHandler($handler);

    /**
     * Check if static assets route been requested.
     *
     * @return bool
     */
    public function checkIfStaticAssetsRequest();
}
