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
$dbname = 'u262763368_kdpat'; // Database name
$username = 'u262763368_kdp631comp'; // Database username
$password = 'Comp@Kdp631'; // Database password

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
