<?php

namespace SigmaPHP\Router\Handlers;

use SigmaPHP\Router\Interfaces\StaticAssetsHandlerInterface;

/**
 * Default Static Assets Handler
 */
class DefaultStaticAssetsHandler implements StaticAssetsHandlerInterface
{
    /**
     * Handle an action triggered by the Router.
     *
     * @param string $resourcePath
     * @return void
     */
    public function handle($resourcePath)
    {
        $resourceFullPath = $resourcePath;

        if (!file_exists($resourceFullPath)) {
            http_response_code(404);
            echo "404 , The Requested URL Was Not Found";
        } else {
            echo file_get_contents($resourceFullPath);
        }
    }
}
