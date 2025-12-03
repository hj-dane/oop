<?php
$host = "localhost";
$dbname = "s24100740_insync";
$username = "s24100740_insync";  // Change to your actual database username
$password = "Admin123";      // Change to your actual database password

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false, // Prevents SQL injection
    ]);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die(json_encode(["status" => "error", "message" => "Database connection failed."]));
}
?>
