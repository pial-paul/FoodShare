<?php

// Database credentials
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');      
define('DB_PASSWORD', '');
define('DB_NAME', 'foodshare_db');

// Attempt to connect to MySQL database
$conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check the connection
if (!$conn) {
    die("ERROR: Could not connect to database. " . mysqli_connect_error());
}

// Set charset to ensure proper character encoding
mysqli_set_charset($conn, "utf8mb4");

/**
 * Helper function to safely escape user input for database operations
 * @param string $data The data to be sanitized
 * @return string The sanitized data
 */

function sanitizeInput($data, $conn) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = mysqli_real_escape_string($conn, $data);
    return $data;
}

/**
 * Helper function to redirect with a message
 * @param string $location The URL to redirect to
 * @param string $message The message to display
 * @param string $type The type of message (success/error)
 */

function redirectWithMessage($location, $message, $type = 'success') {
    $_SESSION['message'] = $message;
    $_SESSION['message_type'] = $type;
    header("Location: $location");
    exit();
}

/**
 * Function to check if user is logged in
 * @return boolean True if logged in, false otherwise
 */

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Function to check if user has specific role
 * @param string $role The role to check
 * @return boolean True if user has the role, false otherwise
 */

function hasRole($role) {
    if (!isLoggedIn()) {
        return false;
    }
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == $role;
}

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>