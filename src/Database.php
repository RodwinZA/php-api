<?php

class Database
{
    private ?PDO $conn = null;
    public function __construct(
        private string $host,
        private string $name,
        private string $user,
        private string $password
    ) {

    }

    public function getConnection(): PDO
    {
        if ($this->conn === null){

            $dsn = "mysql:host={$this->host};dbname={$this->name};charset=utf8";

            $this->conn = new PDO($dsn, $this->user, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ]);
        }
        // The first time this method is executed, the connection will be stored in the
        // property. Subsequent calls of this method will return the value of the
        // property, avoiding multiple connections to the same database in the same request.

        return $this->conn;
    }
}