# Create a RESTful API: build a framework for serving the API

## Start writing the API: enable URL rewriting

We are using the Apache webserver to create our API, but other webservers can also be used.

When running the project inside the Apache server, the URL show the name of the folder, as
well as the script, e.g. `https://localhost/api/index.php`. However, in our RESTful API we want URLs
like `../api/tasks` and/or `../api/tasks/123`.

By default, there is a direct mapping between the request URL and the file and folder on the webserver. So these RESTful URLs, which don't follow this pattern, won't work, as the webserver does not know what to do with them.

We can change this by using the URL rewriting capability of the webserver.

By adding some rewrite rules to the webserver config, we can associate each URL with whatever script we want.
In Apache, we can do this with a `.htaccess` file.

```apacheconf
RewriteEngine On

# Deactivate the URL rewriting for any exisiting file,
# directory or symbolic link.
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-l

# For any URL, run the inde.php script.
# The [L] flag tells the rewrite engine to stop processing.
RewriteRule . index.php [L]
```

Now in the browser we can specify whatever URL we like, and it will always run the `index.php` script.

## The front controller: get the resource, ID and the request method

A resource in a RESTful API has 2 URLs, one for a collection, e.g. `/products` and one for an individual resource, e.g. `/products/123`. We also decide what action to take based on the request method, e.g. `GET, POST PUT PATCH` or `DELETE`.

```php
<?php

// Now we are rewriting all URLs to go through this one script. This is
// known as a front controller. This means that all requests are sent through
// one single script and this script decides what to do.

// Parses the URL and removes the query string.
$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Splits the resource and id from the part
$parts = explode("/", $path);

// The resource is the 3rd part of the array, e.g. `/tasks`
$resource = $parts[2];

// The id is the 4th element of the array.
// If the id is not set we set it to null.
$id = $parts[3] ?? null;

echo $resource, ", ", $id;

// Get the request method
echo $_SERVER['REQUEST_METHOD'];
```

### Use a client for API development: cURL, Postman or HTTPie

Using the browser to test our API only allows us to make `GET` requests. We could use an HTML form to test the `POST` request method, and use Ajax to test other methods. This would effectively be writing a client for working with an API. There are already several of these available though.

#### cURL

We've been using cURL to access various APIs. Curl is also available from the command line and comes installed on most operating systems.

To make a `GET` request in cURL, run: `curl http://localhost:8000/api/tasks` as GET is the default.

To make a different request we add the `-X` or the `--request` flag: curl http://localhost:8000/api/tasks -X PATCH

#### Postman

If you prefer a graphical user interface you can use Postman, which can be run as a standalone application or in the browser.

#### HTTPie

HTTPie is a command-line tool like cURL but is built with API-development in mind. HTTPie is also more user-friendly than cURL.

To make a `GET` request in HTTPie, run: `http http://localhost:8000/api/tasks` as GET is the default.

To make a different request we simply include it before the URL: `http patch http://localhost:8000/api/tasks`

### Set the HTTP status code: best practices

At the moment we're not checking the path of the URL so every request will be successful. But for the purpose of our API we only want two URLs to be valid, `/tasks` and `/tasks/:id`. Any other URL should return a 404 status code.

```php
<?php

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[2];

$id = $parts[3] ?? null;

echo $resource, ", ", $id;

echo $_SERVER['REQUEST_METHOD'];

// The resource will always be `/tasks` in our API.
if ($resource != "tasks") {
    // Sets the reason-phrase automatically
    http_response_code(404);
    exit;
}
```
If you want to change the reason-phrase you will need to replace `http_response_code` with 
```php
header("{$_SERVER['SERVER_PROTOCOL']} 404 Your Reason Phrase");
```
In HTTP2 this is not supported.

### Add a controller class to decide the response

We will add a new directory, `src/TaskController` to decide responses.

```php
<?php

// Matching the classname to the filename is important for
// autoloading.

class TaskController
{
    public function processRequest($method, $id)
    {
        // If the id is null, the url will be for a collection, e.g. /tasks
        if ($id === null) {
            if ($method == "GET") {
                echo "index";
            } elseif ($method == "POST") {
                echo "create";
            }
        } else {
            // If the id is not null, we are dealing with an existing task, e.g. /tasks/123
            switch ($method) {
                case "GET":
                    echo "show $id";
                    break;

                case "PATCH":
                    echo "update $id";
                    break;

                case "DELETE":
                    echo "delete $id";
                    break;
            }
        }
    }
} 
```

### Use Composer's autoloader to load classes automatically

In the front controller, we explicitly load the file where the `TaskController` class is defined using this `require` statement:

```php
require dirname(__DIR__) . "/src/TaskController.php";
```

When adding more files and classes it would be simpler to load these automatically. We could do this by registering an autoloader using the `spl_autoload_register` function, but it is simpler to use composer's autoloader.

First we need to create a `composer.json` file in the root directory and add the following:

```json
{
  "autoload": {
    "psr-4": {
      "": "src/"
    }
  }
}
```

This means that the autoloader will try and load classes from the `src` folder. PSR-4 autoloading simply means that classes are loaded automatically when the filename matches the classname.

To generate the autoload script we run the `composer dump-autoload` command in the terminal, in the same directory as the `composer.json` file. This generates an `autoload.php` script inside the `vendor` folder.

Now we just need to require once and all subsequent classes will be automatically loaded, e.g.

```php
<?php

require dirname(__DIR__) . "/vendor/autoload.php";

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[2];

$id = $parts[3] ?? null;

if ($resource != "tasks") {
    http_response_code(404);
    exit;
}

$controller = new TaskController;

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);
```

### Make debugging easier: add type declarations and enable strict type checking

In the `TaskController` class we have the `processRequest` method which takes two arguments, a request method and a resource id.

To help with debugging we will add type declarations of `string` to both arguments and set its return value to `void` as it does not return anything.

We also need to make the `string $id` argument nullable by placing an `?` in front of the type declaration, else php will throw an error as it is only expecting a string value.

```php
<?php

class TaskController
{
    public function processRequest(string $method, ?string $id): void
    {
        // If the id is null, the url will be for a collection, e.g. /tasks
        if ($id === null) {
            if ($method == "GET") {
                echo "index";
            } elseif ($method == "POST") {
                echo "create";
            }
        } else {
            switch ($method) {
                case "GET":
                    echo "show $id";
                    break;

                case "PATCH":
                    echo "update $id";
                    break;

                case "DELETE":
                    echo "delete $id";
                    break;
            }
        }
    }
}
```

In the front controller (`index.php`) we will enable strict type checking setting the `strict_types` declaration to `1`. This has to be the first statement in the script. As all requests are going through the front controller this setting will be applied globally.

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[2];

$id = $parts[3] ?? null;

if ($resource != "tasks") {
    http_response_code(404);
    exit;
}

$controller = new TaskController;

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);
```

### Always return JSON: add a generic exception handler and JSON Content-Type header

By default, PHP errors are formatted with HTML as it is assumed that it will be viewed in a browser. As this is an API this isn't the case. We will be accessing the API with code or a tool like HTTPie. We will return successful responses as well as errors as JSON as this is the de facto standard.

We can define a generic exception handler using the `set_exception_handler` function. This will catch all unhandled exceptions and allow us to control the output.

In the `src` folder we will create a new class called `ErrorHandler.php`.

```php
<?php

class ErrorHandler
{

    // The Throwable class is the base interface for all errors and exceptions thrown in PHP,
    // so we have access to various methods to get details about the error.
    public static function handleException(Throwable $exception): void
    {
        // Add generic server error
        http_response_code(500);

        echo json_encode([
            "code" => $exception->getCode(),
            "message" => $exception->getMessage(),
            "file" => $exception->getFile(),
            "line" => $exception->getLine()
        ]);
    }
}
```

Inside the front controller we know enable our error handler and set our content-type to JSON globally.

```php
<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

// To enable the error handler
set_exception_handler("ErrorHandler::handleException");

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[2];

$id = $parts[3] ?? null;

if ($resource != "tasks") {
    http_response_code(404);
    exit;
}

// All response bodies in our API will be formatted as JSON, so we can safely put it inside
// the front controller.
header("Content-Type: application/json; charset=UTF-8");

$controller = new TaskController;

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);
```

Note, we probably should not output all the details as given in a production environment, but rather log it and output a generic error message.

### Send a 405 status code and Allow header for invalid request methods

In the task controller we output a response based on the request method and whether the URL contains an id or not. Some actions will be different for the same URL but with a different method. If we use a wrong request method we should output a 405 (Method Not Allowed) status code. When we send this status code we should also send a Allow header that lists the methods that are allowed on the resource.

```php
<?php

class TaskController
{
    public function processRequest(string $method, ?string $id): void
    {
        // If the id is null, the url will be for a collection, e.g. /tasks
        if ($id === null) {
            if ($method == "GET") {
                echo "index";
            } elseif ($method == "POST") {
                echo "create";
            } else {
                $this->respondMethodNotAllowed("GET, POST");
            }
        } else {
            switch ($method) {
                case "GET":
                    echo "show $id";
                    break;

                case "PATCH":
                    echo "update $id";
                    break;

                case "DELETE":
                    echo "delete $id";
                    break;

                default:
                    $this->respondMethodNotAllowed("GET, PATCH, DELETE");
            }
        }
    }

    private function respondMethodNotAllowed(string $allowed_methods): void
    {

        http_response_code(405);
        header("Allow: $allowed_methods");

    }
}
```