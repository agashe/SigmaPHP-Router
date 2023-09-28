# SigmaPHP-Router

A fast and simple router for PHP , you can use for your projects to provide user friendly URLs , you can build your app in a simple  functional style , to write RESTfull API services or to build a fully functional MVC.

## Features

* Support placeholder parameters e.g `{name}`
* Use optional parameters with the route
* Support all HTTP methods GET, POST, PUT ... etc
* Routes can accept multiple HTTP methods
* Support `any` method , so the route can accept all HTTP methods
* Routes Validation using regex expressions
* Actions can be implemented as regular functions or controller's method
* Support for Single Action Controllers
* Middlewares , that can be run before your route
* Route Groups which support middlewares and prefix
* URL generation using the route name
* default page not found (404) handler and you can use your own

## Installation

``` 
composer require agashe/sigmaphp-router
```

## Configurations

Depending on your server , you will need to use one of the provided server configs , including with the router config files for 4 types of servers : Apache , Nginx , Lighttpd and IIS.

To use the router with any of these servers , just copy the corresponding config file from the `configs` folder to the root folder of your project.

For example , in case you are using Apache server , copy the `apache_htaccess` file and rename it to the proper name `.htaccess`

Please note : all of the provided config files , assume that the main entry point to your application is `index.php` located in the root path of your project's directory. If you have different setup for your project , check please the config and make sure it's pointing to the correct path.

So if the `index.php` is located under the `public` folder.
Then in the `.htaccess` file , change `index.php` to `public/index.php` , and the same goes for other servers.

## Documentation

### Basic Setup
In order to start using SigmaPHP-Router in your app , in the main entry point of your app (say for example `index.php` ), you first need define the routes array , then pass that array to the constructor and finally call the the `run()` method.

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Router\Router;

$routes = [
    [
        'name' => 'users.profile',
        'path' => '/users/profile',
        'method' => 'get',
        'controller' => UserController::class,
        'action' => 'profile',
    ],
];

// initialize the router
$router = new Router($routes);

// fire the router
$router->run();
```

Alternatively you can save your routes in a separated file and load that file in your app. Even better you can have multiple route files each serves a different purpose.

web.php

```
<?php

return [
    [
        'name' => 'users.profile',
        'path' => '/users/profile',
        'method' => 'get',
        'controller' => UserController::class,
        'action' => 'profile',
    ],
];

```
api.php

```
<?php

return [
    [
        'name' => 'api.users.profile',
        'path' => '/api/v1/users/profile',
        'method' => 'get',
        'controller' => UserApiController::class,
        'action' => 'profileJson',
    ],
];

```

and finally in `index.php` :

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Router\Router;

$webRoutes = require('web.php');
$apiRoutes = require('api.php');

// initialize the router
$router = new Router(array_merge($webRoutes, $apiRoutes));

// fire the router
$router->run();
```

### Base Path

In case your application exists in sub-folder of your domain for example `http://localhost/my-app` , you can set the root path in the `router` constructor , using the second parameter:

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Router\Router;

$routes = [
    [
        'name' => 'users.profile',
        'path' => '/users/profile',
        'method' => 'get',
        'controller' => UserController::class,
        'action' => 'profile',
    ],
];

// define app base path
const BASE_PATH = '/my-app';

// initialize the router
$router = new Router($routes, BASE_PATH);

// fire the router
$router->run();
```

### HTTP Methods

SigmaPHP-Router support all HTTP methods GET, POST, PUT, DELETE ... etc , a single route can support one or more HTTP methods :

```
$routes = [
    [
        'name' => 'users.profile',
        'path' => '/users/profile',
        'method' => 'get,post',
        'controller' => UserController::class,
        'action' => 'profile',
    ],
];
```

Also SigmaPHP-Router provides a special method type `any` , which allows the route to accept all of the HTTP methods :

```
$routes = [
    [
        'name' => 'users.profile',
        'path' => '/users/profile',
        'method' => 'any',
        'controller' => UserController::class,
        'action' => 'profile',
    ],
];
```

If no HTTP was provided , then the HTP method for the route will be automatically set to GET

### Parameters
Route parameters follow the placeholder style (like Laravel) : 

```
$routes = [
    [
        'name' => 'admin.users.address',
        'path' => '/admin/users/{user_id}/addresses/{address_id}',
        'method' => 'get',
        'controller' => AdminPanelUserController::class,
        'action' => 'getUserAddressDetails',
    ],
];

..... In AdminPanelUserController.php

public function getUserAddressDetails($userId, $addressId) {
    ...
}
```

Also optional parameters can be used by adding `?` to the parameter , but this option can only be used with last parameter of your route :

```
$routes = [
    [
        'name' => 'products.list',
        'path' => '/products/{id?}',
        'method' => 'get',
        'controller' => ProductController::class,
        'action' => 'list',
    ],
];
```

So the `id` in this route can be omitted , so calling `/products` or `/products/15` are fine.

and lastly don't forget to handle the optional parameter in your action , by adding a default value for the action:

```
..... In ProductController.php

public function list($id = null) {
    ...
}
```

### Validation 

A validation rules can be added to your routes , in form of regular expressions :

```
$routes = [
    [
        'name' => 'orders.show',
        'path' => '/orders/details/{order_id}',
        'method' => 'get',
        'controller' => OrderController::class,
        'action' => 'show',
        'validation' => [
            'order_id' => '[0-9]+'
        ]
    ],
];
```
In the example above the router will match only `order_id` that only contains digits , so something like `/orders/details/abcd` won't be matched and the router will return 404 - page not found. 

### Actions

In SigmaPHP-Router an action is the handler which will be executed to process the route functionality.

Actions are divided into 2 categories , first controller based , which simply are classes that contain multiple methods , usually the controller responsible for handling tasks in which grouped by the same model or functionality like `PostController` or `UserLoginController`.

As we saw in the previous examples , we need to pass the controller name and the method name :

```
$routes = [
    [
        'name' => 'orders.show',
        'path' => '/orders/details/{order_id}',
        'method' => 'get',
        'controller' => OrderController::class,
        'action' => 'show',
    ],
];
```

We use the special constant `::class` in order to get the full name including the namespace of the controller. you can instead write the full path if you prefer , for example :

```
$routes = [
    [
        'name' => 'orders.show',
        'path' => '/orders/details/{order_id}',
        'method' => 'get',
        'controller' => App\Web\Controllers\OrderController,
        'action' => 'show',
    ],
];
```

The second type of actions are functions based , and in this case you just add the function name without controller , and the router simply will call that function , so for example :

```
$routes = [
    [
        'name' => 'about_page',
        'path' => '/about',
        'method' => 'get',
        'action' => 'create_about_page',
    ],
];

.... somewhere in your application define the function and call it either in the same index.php or another file

// pages.php
<?php

function create_about_page() {
    print "About Us";
}
```
Finally SigmaPHP-Router also has support for Single Action Controllers , so no need to pass action name ,
and the router will automatically search for the PHP magic method `__invoke()` to run :

```
$routes = [
    [
        'name' => 'notification.send_email',
        'path' => '/notification/send-email',
        'method' => 'post',
        'controller' => SendEmailController::class,
    ],
];
```
And in the SendEmailController :

```
// SendEmailController.php
<?php

class SendEmailController
{
   public function __invoke()
   {
        // .... some code to send email
   } 
}
```

### Middlewares 

Usually in any application you will need to run some checks before allowing the user to perform the action , like for example check if is he logged in , has the proper permissions , and so on.

So out of the box SigmaPHP-Router provides the ability to call middlewares before the execution of your route's action.

```
$routes = [
    [
        'name' => 'orders.create',
        'path' => '/orders',
        'method' => 'post',
        'middlewares' => [
            [AuthMiddleware::class, 'handler'],
            [UserCanCreateOrderMiddleware::class, 'check'],
        ],
        'controller' => OrderController::class,
        'action' => 'create'
    ],
];
```
In case of class based middlewares , the router will require the middleware class name and the name of the method that will be executed.

In addition the middlewares could be written as regular functions , and in this case we pass an array of functions name :

```
$routes = [
    [
        'name' => 'orders.create',
        'path' => '/orders',
        'method' => 'post',
        'middlewares' => ['is_user_auth', 'check_user_permissions'],
        'controller' => OrderController::class,
        'action' => 'create'
    ],
];
```

Creating the middleware classes/functions is completely depending on your application , so in your middleware you could have something similar to :

```
<?php

class AuthMiddleware
{
    public function handler()
    {
        session_start();

        if (empty($_SESSION['user])) {
            header('Location: http://example.com');
            exit();
        }
    }
}
```
### Route Groups
Group routes is an essential feature for any router , so you can apply certain prefix or middleware to a group of routes.

To create a new route group , use the following schema :

```
$routes = [
    [
        'group' => 'api',
        'path' => '/api/v1/',
        'middlewares' => [
            [AuthMiddleware::class, 'handler'],
            [UserAccountIsActiveMiddleware::class, 'handler'],
        ],
        'routes' => [
            [
                'name' => 'users.profile',
                'path' => '/users/profile',
                'method' => 'get',
                'middlewares' => [
                    [CheckIfUserCanEditProfileMiddleware::class, 'handler'],
                ],
                'controller' => UserApiController::class,
                'action' => 'profile'
            ],
        ]
    ],
];
```

The only items required for a group is the group name and the routes array. The name will be added to all of its routes , so in the example above , the final route name will be : `api.users.profile` and the route path : `/api/v1/users/profile`

Both `prefix` and `middlewares` are optional , a routes group could either has prefix , middlewares , both or non of them.

For the routes definition , nothing changed all features are implemented as regular routes.

*Please Note : SigmaPHP-Router doesn't support sub-groups so you can't define a routes group inside another routes group !*

### Page not found handling 

By default in case the requested URI didn't match , the router will return 404 HTTP status code , with simple message `404 , The Requested URL Was Not Found`

You change this default behavior by passing a custom handler name as an argument to the method `setPageNotFoundHandler` 

```
<?php

require 'vendor/autoload.php';

use SigmaPHP\Router\Router;

$routes = [
    [
        'name' => 'users.profile',
        'path' => '/users/profile',
        'method' => 'get',
        'controller' => UserController::class,
        'action' => 'profile',
    ],
];

// initialize the router
$router = new Router($routes);

// set custom 404 (Page Not Found) handler
$router->setPageNotFoundHandler('my_custom_404_handler');

// fire the router
$router->run();

.... and somewhere in your code , you define that function :

function my_custom_404_handler() {
    http_response_code(404);
    echo "<h1>My custom message</h1>";
    exit();
}
```

So now you can add your custom 404 message , page design or redirect the user another route.

And as usual you can instead of using function handler , you can use a class , so you pass class name and the method name :

```
// set custom 404 (Page Not Found) handler
$router->setPageNotFoundHandler([
    MyCustomPageNotFoundHandler::class, 'handle'
]);
```

### URL Generation

In a lot of cases , you will need a way to create an URL for your models , for example a link to show order details , SigmaPHP-Router provides a method called `url` , which accept a route name and parameters array. and generate the URL , let's see an example :

```
$routes = [
    [
        'name' => 'order.items.details',
        'path' => '/order/{order_id}/items/{item_id?}',
        'method' => 'get',
        'controller' => OrderController::class,
        'action' => 'getOrderItems',
    ],
];
```
So to generate an URL for the route above :

```
$orderId = 5;
$itemId = 10;

$itemsURL = $router->url('order.items.details', [
    'order_id' => $orderId,
    'item_id' => $itemId,
]);

..... if we print $itemURL :
http://localhost/order/5/items/10
```

The router will automatically add the host and check if the https is enabled.

Also in case your route doesn't require parameters , or accept optional parameters , you can skip the second parameter.

```
$routes = [
    [
        'name' => 'print_terms_of_usage',
        'path' => '/terms-of-usage',
        'method' => 'get',
        'action' => 'print_terms_of_usage',
    ],
];

$pageURL = $router->url('print_terms_of_usage');

..... if we print $pageURL :
http://localhost/terms-of-usage
```

## Examples

```
$routes = [
    [
        'name' => 'home',
        'path' => '/',
        'method' => 'get',
        'action' => 'home_page',
    ],
    [
        'name' => 'contact_us.show',
        'path' => '/contact-us',
        'method' => 'get',
        'controller' => ContactUsController::class,
        'action' => 'showContactUsForm'
    ],
    [
        'name' => 'contact_us.submit',
        'path' => '/contact-us',
        'method' => 'post',
        'controller' => ContactUsController::class,
        'action' => 'submitContactUsForm'
    ],
    [
        'group' => 'posts',
        'path' => 'posts/',
        'middlewares' => [
            [AuthMiddleware::class, 'handler'],
            [UserIsActiveMiddleware::class, 'handler'],
            [UserCanControlPostsMiddleware::class, 'handler'],
        ],
        'routes' => [
            [
                'name' => 'list',
                'path' => '/{id?}',
                'method' => 'get',
                'controller' => PostController::class,
                'action' => 'index',
                'validation' => [
                    'id' => '[0-9]+'
                ]
            ],
            [
                'name' => 'create',
                'path' => '/create',
                'method' => 'get,post',
                'controller' => PostController::class,
                'action' => 'create'
            ],
            [
                'name' => 'update',
                'path' => '/update/{id}',
                'method' => 'get,patch',
                'controller' => PostController::class,
                'action' => 'update',
                'validation' => [
                    'id' => '[0-9]+'
                ]
            ],
            [
                'name' => 'delete',
                'path' => '/{id}',
                'method' => 'delete',
                'controller' => PostController::class,
                'action' => 'delete',
                'validation' => [
                    'id' => '[0-9]+'
                ]
                'middlewares' => [
                    [CheckPostIsNotPublishedMiddleware::class, 'handler'],
                ],
            ],
        ]
    ]
];
```

## License
(SigmaPHP-Router) released under the terms of the MIT license.
