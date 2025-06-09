<?php
// logout.php
require_once 'includes/db_connect.php'; // Include to ensure session_start() is called

session_unset();   // Unset all session variables
session_destroy(); // Destroy the session

// Clear any messages that might be lingering
unset($_SESSION['message']);
unset($_SESSION['message_type']);

header('location:login.php'); // Redirect to login page after logout
exit();
?>
