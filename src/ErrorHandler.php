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