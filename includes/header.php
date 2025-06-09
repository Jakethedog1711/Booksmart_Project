<?php
// includes/header.php
// This file will be included at the beginning of every page.
// It sets up the HTML head, links to Bootstrap and your custom CSS.

// Ensure db_connect.php is included. This will also start the session.
if (!isset($conn)) {
    require_once 'db_connect.php'; // For root files like index.php, products.php
}
// For admin files, they will use require_once '../includes/db_connect.php';
// So check if session is already started by db_connect.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Function to display messages (replaces old message array logic)
function display_message() {
    if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
        $msg_type = $_SESSION['message_type'] ?? 'info'; // Default to info
        echo '<div class="alert alert-' . htmlspecialchars($msg_type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']); // Clear the message after displaying
        unset($_SESSION['message_type']);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Bookstore</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Your Custom CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Custom Admin CSS (only if this header is used for admin pages too, otherwise move to admin_header.php) -->
    <?php if (strpos($_SERVER['REQUEST_URI'], 'admin') !== false): ?>
        <link rel="stylesheet" href="../assets/css/admin_style.css">
    <?php endif; ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../index.php' : 'index.php'; ?>">Bookstore</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../products.php' : 'products.php'; ?>">Books</a>
                    </li>
                    <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Customer'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../cart.php' : 'cart.php'; ?>">Cart <span class="badge bg-primary" id="cart-item-count">0</span></a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Welcome, <?php echo htmlspecialchars($_SESSION['first_name']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Customer'): ?>
                                    <li><a class="dropdown-item" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../my_account.php' : 'my_account.php'; ?>">My Account</a></li>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin'): ?>
                                    <li><a class="dropdown-item" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? 'index.php' : 'admin/index.php'; ?>">Admin Panel</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../logout.php' : 'logout.php'; ?>">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../login.php' : 'login.php'; ?>">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo (strpos($_SERVER['REQUEST_URI'], 'admin') !== false) ? '../register.php' : 'register.php'; ?>">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="container my-4">
    <?php display_message(); // Display general messages ?>
