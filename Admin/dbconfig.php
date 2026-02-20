<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);


$host = 'localhost'; // Database host
$dbname = 'kdpweb'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 to handle all Unicode characters (emojis, special chars, etc.)
$conn->set_charset("utf8mb4");
?>
