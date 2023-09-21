<?php

/**
 * Example middleware to use in router testing
 */
class ExampleMiddleware
{
    public function handler()
    {
        echo "hello another middleware" . PHP_EOL;
    }
}