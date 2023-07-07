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







