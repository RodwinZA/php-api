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

        $user = $this->user_gateway->getByAPIKey($api_key);

        if ( $user === false) {

            http_response_code(401);
            echo json_encode(["message" => "Invalid API Key"]);
            return false;
        }

        $this->user_id = $user["id"];

        return true;
    }

    public function getUserID(): int
    {
        return $this->user_id;
    }

    public function authenticateAccessToken(): bool
    {
        // Validate if the value of the header matches the authentication scheme
        // we are using (Bearer)
       if ( ! preg_match("/^Bearer\s+(.*)$/", $_SERVER["HTTP_AUTHORIZATION"], $matches) ) {
           echo json_encode(["message" => "incomplete authorization header"]);
           return false;

       }

       $plain_text = base64_decode($matches[1], true);

       if ($plain_text === false) {

           http_response_code(400);
           echo json_encode(["message" => "invalid authorization header"]);
           return false;
       }

       $data = json_decode($plain_text, true);

       if ($data === null) {

           http_response_code(400);
           echo json_encode(["message" => "invalid JSON"]);
           return false;
       }

       // We get the user data directly from the access token

       $this->user_id = $data["id"];

       return true;
    }
}