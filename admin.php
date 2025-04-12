
<?php
// Include database configuration
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to access the admin panel.", "error");
}

// Check if user has the admin role
if (!hasRole("admin")) {
    redirectWithMessage("dashboard.php", "You do not have permission to access the admin panel.", "error");
}

// Initialize variables
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'users';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$message = '';

// Process admin actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Delete user
    if (isset($_POST['delete_user']) && !empty($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        
        // Don't allow admin to delete themselves
        if ($user_id == $_SESSION['user_id']) {
            $message = "You cannot delete your own admin account.";
        } else {
            $delete_sql = "DELETE FROM users WHERE id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_sql);
            mysqli_stmt_bind_param($delete_stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($delete_stmt)) {
                $message = "User deleted successfully.";
            } else {
                $message = "Error deleting user: " . mysqli_error($conn);
            }
            mysqli_stmt_close($delete_stmt);
        }
    }
    
    // Delete donation
    if (isset($_POST['delete_donation']) && !empty($_POST['donation_id'])) {
        $donation_id = intval($_POST['donation_id']);
        
        // First check if there's an image to delete
        $image_sql = "SELECT image FROM donations WHERE id = ?";
        $image_stmt = mysqli_prepare($conn, $image_sql);
        mysqli_stmt_bind_param($image_stmt, "i", $donation_id);
        mysqli_stmt_execute($image_stmt);
        $image_result = mysqli_stmt_get_result($image_stmt);
        
        if ($image_row = mysqli_fetch_assoc($image_result)) {
            if (!empty($image_row['image']) && file_exists($image_row['image'])) {
                unlink($image_row['image']); // Delete the image file
            }
        }
        mysqli_stmt_close($image_stmt);
        
        // Now delete the donation record
        $delete_sql = "DELETE FROM donations WHERE id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_sql);
        mysqli_stmt_bind_param($delete_stmt, "i", $donation_id);
        
        if (mysqli_stmt_execute($delete_stmt)) {
            $message = "Donation deleted successfully.";
        } else {
            $message = "Error deleting donation: " . mysqli_error($conn);
        }
        mysqli_stmt_close($delete_stmt);
    }
    
    // Cancel request
    if (isset($_POST['cancel_request']) && !empty($_POST['request_id'])) {
        $request_id = intval($_POST['request_id']);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Get the donation ID associated with this request
            $donation_sql = "SELECT donation_id FROM requests WHERE id = ?";
            $donation_stmt = mysqli_prepare($conn, $donation_sql);
            mysqli_stmt_bind_param($donation_stmt, "i", $request_id);
            mysqli_stmt_execute($donation_stmt);
            $donation_result = mysqli_stmt_get_result($donation_stmt);
            
            if ($donation_row = mysqli_fetch_assoc($donation_result)) {
                $donation_id = $donation_row['donation_id'];
                mysqli_stmt_close($donation_stmt);
                
                // Update donation status back to available
                $update_sql = "UPDATE donations SET status = 'available' WHERE id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                mysqli_stmt_bind_param($update_stmt, "i", $donation_id);
                
                if (!mysqli_stmt_execute($update_stmt)) {
                    throw new Exception("Failed to update donation status.");
                }
                mysqli_stmt_close($update_stmt);
                
                // Delete the request
                $delete_sql = "DELETE FROM requests WHERE id = ?";
                $delete_stmt = mysqli_prepare($conn, $delete_sql);
                mysqli_stmt_bind_param($delete_stmt, "i", $request_id);
                
                if (!mysqli_stmt_execute($delete_stmt)) {
                    throw new Exception("Failed to cancel request.");
                }
                mysqli_stmt_close($delete_stmt);
                
                // Commit the transaction
                mysqli_commit($conn);
                $message = "Request cancelled successfully.";
                
            } else {
                throw new Exception("Request not found.");
            }
            
        } catch (Exception $e) {
            // Rollback the transaction
            mysqli_rollback($conn);
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Fetch users data
$users_sql = "SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC";
$users_result = mysqli_query($conn, $users_sql);

// Fetch donations data with donor names
$donations_sql = "SELECT d.*, u.name AS donor_name 
                 FROM donations d 
                 JOIN users u ON d.donor_id = u.id 
                 ORDER BY d.created_at DESC";
$donations_result = mysqli_query($conn, $donations_sql);

// Fetch requests data with receiver, donor and donation details
$requests_sql = "SELECT r.*, 
                 u_receiver.name AS receiver_name, 
                 u_donor.name AS donor_name,
                 d.food_name, d.status AS donation_status
                 FROM requests r
                 JOIN users u_receiver ON r.receiver_id = u_receiver.id
                 JOIN donations d ON r.donation_id = d.id
                 JOIN users u_donor ON d.donor_id = u_donor.id
                 ORDER BY r.created_at DESC";
$requests_result = mysqli_query($conn, $requests_sql);

// Get counts for dashboard
$users_count = mysqli_num_rows($users_result);

$donations_count_sql = "SELECT COUNT(*) as count FROM donations";
$donations_count_result = mysqli_query($conn, $donations_count_sql);
$donations_count = mysqli_fetch_assoc($donations_count_result)['count'];

$requests_count_sql = "SELECT COUNT(*) as count FROM requests";
$requests_count_result = mysqli_query($conn, $requests_count_sql);
$requests_count = mysqli_fetch_assoc($requests_count_result)['count'];

// Get donation status counts for dashboard
$status_sql = "SELECT status, COUNT(*) as count FROM donations GROUP BY status";
$status_result = mysqli_query($conn, $status_sql);
$status_counts = array(
    'available' => 0,
    'requested' => 0,
    'picked_up' => 0,
    'expired' => 0
);

while ($status_row = mysqli_fetch_assoc($status_result)) {
    $status_counts[$status_row['status']] = $status_row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - FoodShare</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .stats-card {
            border-left: 5px solid;
            margin-bottom: 20px;
        }
        .stats-card.users {
            border-left-color: #007bff;
        }
        .stats-card.donations {
            border-left-color: #28a745;
        }
        .stats-card.requests {
            border-left-color: #ffc107;
        }
        .stats-card .icon {
            font-size: 2rem;
            opacity: 0.7;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-2">
                <div class="card mb-4">
                    <div class="card-header bg-dark text-white">
                        Admin Menu
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="admin.php?tab=dashboard" class="list-group-item list-group-item-action <?php if($tab == 'dashboard') echo 'active'; ?>">
                            <i class="fas fa-tachometer-alt mr-2"></i> Dashboard
                        </a>
                        <a href="admin.php?tab=users" class="list-group-item list-group-item-action <?php if($tab == 'users') echo 'active'; ?>">
                            <i class="fas fa-users mr-2"></i> Users
                        </a>
                        <a href="admin.php?tab=donations" class="list-group-item list-group-item-action <?php if($tab == 'donations') echo 'active'; ?>">
                            <i class="fas fa-box-open mr-2"></i> Donations
                        </a>
                        <a href="admin.php?tab=requests" class="list-group-item list-group-item-action <?php if($tab == 'requests') echo 'active'; ?>">
                            <i class="fas fa-hand-holding-heart mr-2"></i> Requests
                        </a>
                        <a href="dashboard.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt mr-2"></i> Exit Admin
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-10">
                <?php if(!empty($message)): ?>
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>
                
                <?php if($tab == 'dashboard'): ?>
                    <!-- Dashboard Overview -->
                    <h2 class="mb-4">Admin Dashboard</h2>
                    
                    <div class="row">
                        <!-- Users Stats -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card users">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Users</h5>
                                            <span class="h2 font-weight-bold mb-0"><?php echo $users_count; ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon text-primary">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Donations Stats -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card donations">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Donations</h5>
                                            <span class="h2 font-weight-bold mb-0"><?php echo $donations_count; ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon text-success">
                                                <i class="fas fa-box-open"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Requests Stats -->
                        <div class="col-xl-3 col-md-6">
                            <div class="card stats-card requests">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col">
                                            <h5 class="card-title text-uppercase text-muted mb-0">Requests</h5>
                                            <span class="h2 font-weight-bold mb-0"><?php echo $requests_count; ?></span>
                                        </div>
                                        <div class="col-auto">
                                            <div class="icon text-warning">
                                                <i class="fas fa-hand-holding-heart"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-4">
                        <!-- Donation Status Chart -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Donation Status</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="donationStatusChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="col-lg-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0">Recent Activity</h5>
                                </div>
                                <div class="card-body">
                                    <ul class="list-group">
                                        <?php 
                                        // Reset the result pointers
                                        mysqli_data_seek($donations_result, 0);
                                        mysqli_data_seek($requests_result, 0);
                                        
                                        $activities = [];
                                        
                                        // Get 3 most recent donations
                                        $count = 0;
                                        while ($row = mysqli_fetch_assoc($donations_result)) {
                                            $activities[] = [
                                                'type' => 'donation',
                                                'date' => $row['created_at'],
                                                'data' => $row
                                            ];
                                            $count++;
                                            if ($count >= 3) break;
                                        }
                                        
                                        // Get 3 most recent requests
                                        $count = 0;
                                        while ($row = mysqli_fetch_assoc($requests_result)) {
                                            $activities[] = [
                                                'type' => 'request',
                                                'date' => $row['created_at'],
                                                'data' => $row
                                            ];
                                            $count++;
                                            if ($count >= 3) break;
                                        }
                                        
                                        // Sort by date, newest first
                                        usort($activities, function($a, $b) {
                                            return strtotime($b['date']) - strtotime($a['date']);
                                        });
                                        
                                        // Display the 5 most recent activities
                                        $count = 0;
                                        foreach ($activities as $activity) {
                                            if ($count >= 5) break;
                                            
                                            if ($activity['type'] == 'donation') {
                                                $icon = '<i class="fas fa-box-open text-success"></i>';
                                                $text = '<strong>' . htmlspecialchars($activity['data']['donor_name']) . '</strong> donated <strong>' . 
                                                         htmlspecialchars($activity['data']['food_name']) . '</strong>';
                                            } else {
                                                $icon = '<i class="fas fa-hand-holding-heart text-warning"></i>';
                                                $text = '<strong>' . htmlspecialchars($activity['data']['receiver_name']) . '</strong> requested <strong>' . 
                                                         htmlspecialchars($activity['data']['food_name']) . '</strong> from <strong>' . 
                                                         htmlspecialchars($activity['data']['donor_name']) . '</strong>';
                                            }
                                            
                                            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
                                            echo '<div>' . $icon . ' ' . $text . '</div>';
                                            echo '<small class="text-muted">' . date('M d, H:i', strtotime($activity['date'])) . '</small>';
                                            echo '</li>';
                                            
                                            $count++;
                                        }
                                        
                                        if (count($activities) == 0) {
                                            echo '<li class="list-group-item">No recent activity.</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif($tab == 'users'): ?>
                    <!-- Users Management -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">Users Management</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Registered</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = mysqli_fetch_assoc($users_result)): ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $row['role'] == 'admin' ? 'danger' : 
                                                        ($row['role'] == 'donor' ? 'success' : 'info'); 
                                                ?>">
                                                    <?php echo ucfirst($row['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <?php if($row['id'] != $_SESSION['user_id']): ?>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="user_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_user" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <span class="text-muted">Current Admin</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif($tab == 'donations'): ?>
                    <!-- Donations Management -->
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0">Donations Management</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Food</th>
                                            <th>Donor</th>
                                            <th>Quantity</th>
                                            <th>Location</th>
                                            <th>Expiry</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset result pointer
                                        mysqli_data_seek($donations_result, 0);
                                        while ($row = mysqli_fetch_assoc($donations_result)): 
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['food_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                                            <td><?php echo htmlspecialchars(substr($row['location'], 0, 30) . (strlen($row['location']) > 30 ? '...' : '')); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($row['expiry_date'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $row['status'] == 'available' ? 'success' : 
                                                        ($row['status'] == 'requested' ? 'warning' : 
                                                        ($row['status'] == 'picked_up' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $row['status'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to delete this donation?');">
                                                    <input type="hidden" name="donation_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="delete_donation" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                <?php elseif($tab == 'requests'): ?>
                    <!-- Requests Management -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h4 class="mb-0">Requests Management</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Food Item</th>
                                            <th>Requester</th>
                                            <th>Donor</th>
                                            <th>Request Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        // Reset result pointer
                                        mysqli_data_seek($requests_result, 0);
                                        while ($row = mysqli_fetch_assoc($requests_result)): 
                                        ?>
                                        <tr>
                                            <td><?php echo $row['id']; ?></td>
                                            <td><?php echo htmlspecialchars($row['food_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['receiver_name']); ?></td>
                                            <td><?php echo htmlspecialchars($row['donor_name']); ?></td>
                                            <td><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></td>
                                            <td>
                                                <span class="badge badge-<?php 
                                                    echo $row['status'] == 'pending' ? 'warning' : 
                                                        ($row['status'] == 'approved' ? 'success' : 
                                                        ($row['status'] == 'completed' ? 'info' : 'danger')); 
                                                ?>">
                                                    <?php echo ucfirst($row['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="post" onsubmit="return confirm('Are you sure you want to cancel this request? This will make the donation available again.');">
                                                    <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                                                    <button type="submit" name="cancel_request" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Cancel
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    
    <!-- Include Chart.js for dashboard charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <?php if($tab == 'dashboard'): ?>
    <script>
        // Donation Status Chart
        var ctx = document.getElementById('donationStatusChart').getContext('2d');
        var donationStatusChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Available', 'Requested', 'Picked Up', 'Expired'],
                datasets: [{
                    data: [
                        <?php echo $status_counts['available']; ?>,
                        <?php echo $status_counts['requested']; ?>,
                        <?php echo $status_counts['picked_up']; ?>,
                        <?php echo $status_counts['expired']; ?>
                    ],
                    backgroundColor: [
                        '#28a745', // Success green
                        '#ffc107', // Warning yellow
                        '#17a2b8', // Info blue
                        '#dc3545'  // Danger red
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.raw || 0;
                                var total = context.dataset.data.reduce((a, b) => a + b, 0);
                                var percentage = Math.round((value / total) * 100);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>
