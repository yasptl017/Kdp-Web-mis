<?php


error_reporting(E_ALL);
ini_set('display_errors', 1);


$host = 'localhost'; // Database host
$dbname = 'u262763368_kdpweb'; // Database name
$username = 'u262763368_kdp631'; // Database username
$password = 'Kdp@631#cm'; // Database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
