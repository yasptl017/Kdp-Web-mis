<?php
require_once __DIR__ . '/auth.php';

if (PHP_SAPI !== 'cli') {
    $currentScript = basename((string)($_SERVER['SCRIPT_NAME'] ?? ''));
    $publicScripts = ['index.php'];
    if (!in_array($currentScript, $publicScripts, true)) {
        require_login();
    }
}

$host = 'localhost'; // Database host
$dbname = 'kdpatt'; // Database name
$username = 'root'; // Database username
$password = ''; // Database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
