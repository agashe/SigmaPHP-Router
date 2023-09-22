<?php

/**
 * Example middleware to use in router testing
 */
class ExampleMiddleware
{
    public function handler()
    {
        echo 'Middleware is working.';
    }
}