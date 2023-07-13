<?php

declare(strict_types=1);

require dirname(__DIR__) . "/vendor/autoload.php";

set_exception_handler("ErrorHandler::handleException");

$dotenv = \Dotenv\Dotenv::createImmutable(dirname(__DIR__));

$dotenv->load();

$path =  parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

$parts = explode("/", $path);

$resource = $parts[2];

$id = $parts[3] ?? null;

if ($resource != "tasks") {
    http_response_code(404);
    exit;
}

header("Content-Type: application/json; charset=UTF-8");

$database = new Database($_ENV["DB_HOST"], $_ENV["DB_NAME"], $_ENV["DB_USER"], $_ENV["DB_PASS"]);

// When we create an object of the controller class we need to pass in an object of the
// `TaskGateway` class.
$task_gateway = new TaskGateway($database);

$controller = new TaskController($task_gateway);

$controller->processRequest($_SERVER['REQUEST_METHOD'], $id);