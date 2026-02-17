<?php
// Database Configuration
$host = '127.0.0.1';
$db_name = 'perfume_shop';
$username = 'root';
$password = ''; // Default XAMPP password is empty

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    die("Database Connection Failed: " . $e->getMessage());
}
?>
