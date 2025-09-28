<?php

use SigmaPHP\Router\Tests\Examples\Controller as ExampleController;
use SigmaPHP\Router\Tests\Examples\GroupController as ExampleGroupController;
use SigmaPHP\Router\Tests\Examples\Middleware as ExampleMiddleware;
use SigmaPHP\Router\Tests\Examples\SingleActionController
    as ExampleSingleActionController;  

return [
    [
        'name' => 'test1',
        'path' => '/test1/static',
        'method' => 'get',
        'action' => 'route_handler_a',
    ],
    [
        'name' => 'test2',
        'path' => '/test2/{data}',
        'method' => 'get',
        'action' => 'route_handler_b'
    ],
    [
        'name' => 'test3',
        'path' => '/test3/{data1}/{data2}/test/{data3}',
        'method' => 'get',
        'action' => 'route_handler_c'
    ],
    [
        'name' => 'test4',
        'path' => '/test4/optional/param/{data?}',
        'method' => 'get',
        'action' => 'route_handler_d'
    ],
    [
        'name' => 'test5',
        'path' => '/test5/controller',
        'method' => 'get',
        'controller' => ExampleController::class,
        'action' => 'index'
    ],
    [
        'name' => 'test6',
        'path' => '/test6/middleware',
        'method' => 'get',
        'middlewares' => [
            [ExampleMiddleware::class, 'handler'],
        ],
        'controller' => ExampleController::class,
        'action' => 'index'
    ],
    [
        'name' => 'test7',
        'path' => '/test7/validation/{data}',
        'method' => 'get',
        'action' => 'route_handler_d',
        'validation' => [
            'data' => '[0-9]+'
        ]
    ],
    [
        'name' => 'test8',
        'path' => '/test8/any-method/{data}',
        'method' => 'any',
        'action' => 'route_handler_d',
    ],
    [
        'group' => 'test_group',
        'prefix' => 'test-group/',
        'middlewares' => [
            [ExampleMiddleware::class, 'handler'],
        ],
        'routes' => [
            [
                'name' => 'test9',
                'path' => '/test9',
                'method' => 'post',
                'controller' => ExampleController::class,
                'action' => 'index'
            ],
        ]
    ],
    [
        'name' => 'test10',
        'path' => '/test10/default-method',
        'action' => 'route_handler_a'
    ],
    [
        'name' => 'test11',
        'path' => '/test11/single-action-controller',
        'method' => 'get',
        'controller' => ExampleSingleActionController::class,
    ],
    [
        'name' => 'test12',
        'path' => '/test12/regular-function-middleware',
        'middlewares' => ['custom_middleware'],
        'action' => 'route_handler_a'
    ],
    [
        'path' => '/test13/route-without-name',
        'action' => 'route_handler_a'
    ],
    [
        'group' => 'test_group_optional',
        'routes' => [
            [
                'path' => '/test14',
                'action' => 'route_handler_a'
            ],
        ]
    ],
    [
        'name' => 'test15',
        'path' => '/{data1}/test15/{data2}/test/{data3}',
        'method' => 'get',
        'action' => 'route_handler_c'
    ],
    [
        'group' => 'test_group_controller',
        'prefix' => 'test-group-controller/',
        'controller' => ExampleGroupController::class,
        'routes' => [
            [
                'name' => 'test16',
                'path' => '/test16',
                'method' => 'get',
                'action' => 'home'
            ],
            [
                'name' => 'test17',
                'path' => '/test17',
                'method' => 'post',
                'controller' => ExampleController::class,
                'action' => 'index'
            ],
            [
                'name' => 'test18',
                'path' => '/test18',
                'method' => 'get',
                'action' => 'about'
            ],
        ]
    ],
];