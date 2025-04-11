<?php
// Include database configuration
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to request donations.", "error");
}

// Check if user has the receiver role
if (!hasRole("receiver")) {
    redirectWithMessage("dashboard.php", "You do not have permission to request donations.", "error");
}

// Check if the donation ID is provided
if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['donation_id']) || empty($_POST['donation_id'])) {
    redirectWithMessage("browse.php", "Invalid request. Please try again.", "error");
}

// Get the donation ID
$donation_id = sanitizeInput($_POST['donation_id'], $conn);
$receiver_id = $_SESSION['user_id'];

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Check if the donation exists and is available
    $check_sql = "SELECT * FROM donations WHERE id = ? AND status = 'available'";
    $check_stmt = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($check_stmt, "i", $donation_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if (mysqli_num_rows($check_result) != 1) {
        // Donation not found or not available
        throw new Exception("This donation is no longer available.");
    }
    
    $donation = mysqli_fetch_assoc($check_result);
    mysqli_stmt_close($check_stmt);
    
    // Check if user has already requested this donation
    $existing_request_sql = "SELECT * FROM requests WHERE donation_id = ? AND receiver_id = ?";
    $existing_request_stmt = mysqli_prepare($conn, $existing_request_sql);
    mysqli_stmt_bind_param($existing_request_stmt, "ii", $donation_id, $receiver_id);
    mysqli_stmt_execute($existing_request_stmt);
    $existing_request_result = mysqli_stmt_get_result($existing_request_stmt);
    
    if (mysqli_num_rows($existing_request_result) > 0) {
        // User has already requested this donation
        throw new Exception("You have already requested this donation.");
    }
    mysqli_stmt_close($existing_request_stmt);
    
    // Insert the request
    $insert_sql = "INSERT INTO requests (receiver_id, donation_id, status) VALUES (?, ?, 'pending')";
    $insert_stmt = mysqli_prepare($conn, $insert_sql);
    mysqli_stmt_bind_param($insert_stmt, "ii", $receiver_id, $donation_id);
    
    if (!mysqli_stmt_execute($insert_stmt)) {
        throw new Exception("Failed to submit request. Please try again.");
    }
    mysqli_stmt_close($insert_stmt);
    
    // Update the donation status to 'requested'
    $update_sql = "UPDATE donations SET status = 'requested' WHERE id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "i", $donation_id);
    
    if (!mysqli_stmt_execute($update_stmt)) {
        throw new Exception("Failed to update donation status. Please try again.");
    }
    mysqli_stmt_close($update_stmt);
    
    // Commit the transaction
    mysqli_commit($conn);
    
    // Get donor details for the confirmation message
    $donor_sql = "SELECT u.name, d.food_name FROM users u JOIN donations d ON u.id = d.donor_id WHERE d.id = ?";
    $donor_stmt = mysqli_prepare($conn, $donor_sql);
    mysqli_stmt_bind_param($donor_stmt, "i", $donation_id);
    mysqli_stmt_execute($donor_stmt);
    $donor_result = mysqli_stmt_get_result($donor_stmt);
    $donor_info = mysqli_fetch_assoc($donor_result);
    mysqli_stmt_close($donor_stmt);
    
    // Success message
    $success_message = "Your request for " . $donor_info['food_name'] . " has been submitted successfully! ";
    $success_message .= "The donor (" . $donor_info['name'] . ") will be notified of your request.";
    
    redirectWithMessage("dashboard.php", $success_message, "success");
    
} catch (Exception $e) {
    // Rollback the transaction if there's an error
    mysqli_rollback($conn);
    redirectWithMessage("browse.php", $e->getMessage(), "error");
} finally {
    // Close the connection
    mysqli_close($conn);
}
?>
