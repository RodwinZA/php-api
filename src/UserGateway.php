<?php

class UserGateway
{
    private PDO $conn;

    public function __construct(Database $database)
    {
        $this->conn = $database->getConnection();
    }

    public function getByAPIKey(string $key): array | false
    {
        // We only need the API key to authenticate the request, so we
        // don't use the password or username yet
        $sql = "SELECT * FROM user WHERE api_key = :api_key";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":api_key", $key, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByUsername(string $username): mixed
    {
        $sql = "SELECT * FROM user WHERE username = :username";

        $stmt = $this->conn->prepare($sql);
        $stmt->bindValue(":username", $username, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}