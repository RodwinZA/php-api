<?php

// Autoload classes in the project
require __DIR__ . "/vendor/autoload.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Connect to the database using existing database class

    // Specify the current folder as the location of the dotenv file
    $dotenv = \Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $database = new Database(
            $_ENV["DB_HOST"],
            $_ENV["DB_NAME"],
            $_ENV["DB_USER"],
            $_ENV["DB_PASS"]
    );

    $conn = $database->getConnection();

    $sql = "INSERT INTO user (name, username, password_hash, api_key)
            VALUES (:name, :username, :password_hash, :api_key)";

    $stmt = $conn->prepare($sql);

    // Hash the password before storing it in the database
    $password_hash = password_hash($_POST["password"], PASSWORD_DEFAULT);

    // Generate an API key
    $api_key = bin2hex(random_bytes(16));


    // Bind values for the placeholders
    $stmt->bindValue(":name", $_POST["name"], PDO::PARAM_STR);
    $stmt->bindValue(":username", $_POST["username"], PDO::PARAM_STR);
    $stmt->bindValue(":password_hash", $password_hash, PDO::PARAM_STR);
    $stmt->bindValue(":api_key", $api_key, PDO::PARAM_STR);

    $stmt->execute();

    echo "Thank you for registering. Your API key is " . $api_key;
}



?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@1/css/pico.min.css">
</head>
<body>

<main class="container">
    <h1>Register</h1>

    <form method="post">
        <label for="name">
            Name
            <input type="text" name="name" id="name">
        </label>

        <label for="username">
            Username
            <input type="text" name="username" id="username">
        </label>

        <label for="password">
            Password
            <input type="password" name="password" id="password">
        </label>

        <button>Register</button>
    </form>
</main>
</body>
</html>