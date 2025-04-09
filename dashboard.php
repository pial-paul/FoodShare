<?php
// Include database configuration
require_once 'config/config.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// Get user information
$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];
$user_email = $_SESSION["user_email"];
$user_role = $_SESSION["user_role"];

// Initialize variables for statistics
$donation_count = 0;
$request_count = 0;
$total_active_donations = 0;

// For donors: Get donation statistics
if ($user_role == "donor") {
    // Count total donations by this donor
    $sql = "SELECT COUNT(*) as total FROM donations WHERE donor_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $donation_count = $row["total"];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Count active donations by this donor
    $sql = "SELECT COUNT(*) as active FROM donations WHERE donor_id = ? AND status = 'available'";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $total_active_donations = $row["active"];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Count total requests for this donor's food
    $sql = "SELECT COUNT(*) as requests FROM requests r 
            INNER JOIN donations d ON r.donation_id = d.id 
            WHERE d.donor_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $request_count = $row["requests"];
            }
        }
        mysqli_stmt_close($stmt);
    }
}
// For receivers: Get request statistics
else if ($user_role == "receiver") {
    // Count total requests made by this receiver
    $sql = "SELECT COUNT(*) as total FROM requests WHERE receiver_id = ?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $request_count = $row["total"];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Count active food donations available
    $sql = "SELECT COUNT(*) as active FROM donations WHERE status = 'available'";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $total_active_donations = $row["active"];
            }
        }
        mysqli_stmt_close($stmt);
    }
}
// For admin: Get all statistics
else if ($user_role == "admin") {
    // Count total donations
    $sql = "SELECT COUNT(*) as total FROM donations";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $donation_count = $row["total"];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Count total requests
    $sql = "SELECT COUNT(*) as total FROM requests";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $request_count = $row["total"];
            }
        }
        mysqli_stmt_close($stmt);
    }
    
    // Count active donations
    $sql = "SELECT COUNT(*) as active FROM donations WHERE status = 'available'";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (mysqli_stmt_execute($stmt)) {
            $result = mysqli_stmt_get_result($stmt);
            if ($row = mysqli_fetch_assoc($result)) {
                $total_active_donations = $row["active"];
            }
        }
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - FoodShare</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <?php
        // Display welcome message and success message if any
        if (isset($_SESSION['message'])) {
            echo '<div class="alert alert-' . $_SESSION['message_type'] . ' alert-dismissible fade show" role="alert">
                    ' . $_SESSION['message'] . '
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                  </div>';
            unset($_SESSION['message']);
            unset($_SESSION['message_type']);
        }
        ?>
        
        <div class="jumbotron bg-light">
            <h1 class="display-4">Welcome, <?php echo htmlspecialchars($user_name); ?>!</h1>
            <p class="lead">
                You are logged in as a <strong><?php echo ucfirst(htmlspecialchars($user_role)); ?></strong>.
                <?php if ($user_role == "donor") { ?>
                    Thank you for your generosity in sharing food with those in need.
                <?php } else if ($user_role == "receiver") { ?>
                    Browse available food donations and request what you need.
                <?php } else if ($user_role == "admin") { ?>
                    Manage users, donations, and oversee the platform.
                <?php } ?>
            </p>
            <hr class="my-4">
        </div>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php if ($user_role == "donor" || $user_role == "admin") { ?>
            <div class="col-md-4 mb-3">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Total Donations</h6>
                                <h1><?php echo $donation_count; ?></h1>
                            </div>
                            <i class="fas fa-gift fa-3x"></i>
                        </div>
                    </div>
                    <?php if ($user_role == "donor") { ?>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a href="donate.php" class="text-white">Donate Food</a>
                        <i class="fas fa-angle-right"></i>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
            
            <div class="col-md-4 mb-3">
                <div class="card bg-success text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Active Donations</h6>
                                <h1><?php echo $total_active_donations; ?></h1>
                            </div>
                            <i class="fas fa-apple-alt fa-3x"></i>
                        </div>
                    </div>
                    <?php if ($user_role == "receiver") { ?>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <a href="browse.php" class="text-white">Browse Food</a>
                        <i class="fas fa-angle-right"></i>
                    </div>
                    <?php } ?>
                </div>
            </div>
            
            <div class="col-md-4 mb-3">
                <div class="card bg-warning text-white h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-uppercase">Food Requests</h6>
                                <h1><?php echo $request_count; ?></h1>
                            </div>
                            <i class="fas fa-hands-helping fa-3x"></i>
                        </div>
                    </div>
                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <?php if ($user_role == "donor") { ?>
                        <a href="#" class="text-white">View Requests</a>
                        <?php } else if ($user_role == "receiver") { ?>
                        <a href="#" class="text-white">My Requests</a>
                        <?php } else { ?>
                        <a href="#" class="text-white">Manage Requests</a>
                        <?php } ?>
                        <i class="fas fa-angle-right"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($user_role == "donor") { ?>
                            <div class="col-md-4 mb-3">
                                <a href="donate.php" class="btn btn-primary btn-block py-3">
                                    <i class="fas fa-plus-circle mr-2"></i> Donate Food
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="#" class="btn btn-info btn-block py-3">
                                    <i class="fas fa-list mr-2"></i> My Donations
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="#" class="btn btn-warning btn-block py-3">
                                    <i class="fas fa-bell mr-2"></i> Pending Requests
                                </a>
                            </div>
                            <?php } else if ($user_role == "receiver") { ?>
                            <div class="col-md-4 mb-3">
                                <a href="browse.php" class="btn btn-success btn-block py-3">
                                    <i class="fas fa-search mr-2"></i> Browse Food
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="#" class="btn btn-info btn-block py-3">
                                    <i class="fas fa-clipboard-list mr-2"></i> My Requests
                                </a>
                            </div>
                            <div class="col-md-4 mb-3">
                                <a href="#" class="btn btn-warning btn-block py-3">
                                    <i class="fas fa-history mr-2"></i> Request History
                                </a>
                            </div>
                            <?php } else if ($user_role == "admin") { ?>
                            <div class="col-md-3 mb-3">
                                <a href="admin.php" class="btn btn-danger btn-block py-3">
                                    <i class="fas fa-tachometer-alt mr-2"></i> Admin Panel
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-primary btn-block py-3">
                                    <i class="fas fa-users mr-2"></i> Manage Users
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-success btn-block py-3">
                                    <i class="fas fa-apple-alt mr-2"></i> Manage Donations
                                </a>
                            </div>
                            <div class="col-md-3 mb-3">
                                <a href="#" class="btn btn-warning btn-block py-3">
                                    <i class="fas fa-hands-helping mr-2"></i> Manage Requests
                                </a>
                            </div>
                            <?php } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activity -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Recent Activity</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted">Your recent activities will appear here.</p>
                        <!-- Add recent activity content here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
