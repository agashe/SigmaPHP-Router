<?php

/**
 * Example controller to use in router testing
 */
class ExampleController
{
    public function foo($bar = null)
    {
        echo "Basic Controller Action" . PHP_EOL;    
        echo "The data was received : {$_GET['name']}" . PHP_EOL;    
    }

    public function test()
    {
        echo "The post title is : {$_POST['title']}" . PHP_EOL;    
    }

    public function index($id = 1)
    {
        echo "The post id is : {$id}" . PHP_EOL;    
    }
}