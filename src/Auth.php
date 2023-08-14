<?php

class Auth
{
    // Store current user id
    private int $user_id;
    public function __construct(private UserGateway $user_gateway)
    {
    }
    public function authenticateAPIKey(): bool
    {
        if (empty($_SERVER["HTTP_X_API_KEY"])) {

            http_response_code(400);
            echo json_encode(["message" => "Missing API key"]);
            return false;
        }

        $api_key = $_SERVER["HTTP_X_API_KEY"];

        // Instead of comparing the return value directly, we assign the
        // value to a variable and return that.

        $user = $this->user_gateway->getByAPIKey($api_key);

        if ( $user === false) {

            http_response_code(401);
            echo json_encode(["message" => "Invalid API Key"]);
            return false;
        }

        // If auth is successful, assign user_id to the property
        $this->user_id = $user["id"];

        return true;
    }

    public function getUserID(): int
    {
        return $this->user_id;
    }
}