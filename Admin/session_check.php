
<?php 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If NOT logged in → redirect to login
if(!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true){
    header("Location: login.php");
    exit;
}

// OPTIONAL: Admin only access
// if($_SESSION['role'] !== "Admin"){
//     echo "Access Denied";
//     exit;
// }
?>
