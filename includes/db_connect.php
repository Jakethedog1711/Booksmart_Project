<?php
// includes/db_connect.php

$servername = "localhost"; // Usually 'localhost' for XAMPP
$username = "root";        // Default XAMPP MySQL username
$password = "";            // Default XAMPP MySQL password (empty)
$dbname = "shop_db";  // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to UTF-8 for proper handling of various characters
$conn->set_charset("utf8mb4");

// Start session for user management (login, cart, etc.)
session_start();

// You can include common functions here or in a separate functions.php
// For example, a function to sanitize input
function sanitize_input($data) {
    global $conn; // Access the global connection object
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data); // Escape special characters for SQL
    return $data;
}

// Function to hash passwords (use password_hash for security)
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Function to verify passwords
function verify_password($password, $hashed_password) {
    return password_verify($password, $hashed_password);
}
?>
