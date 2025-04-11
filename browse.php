<?php
// Include database configuration
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to browse available donations.", "error");
}

// Check if user has the receiver role
if (!hasRole("receiver")) {
    redirectWithMessage("dashboard.php", "You do not have permission to access this page.", "error");
}

// Initialize filter variables
$search = $location_filter = "";
$order_by = "created_at DESC"; // Default sorting by newest first

// Process search and filter requests
if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['search'])) {
    $search = sanitizeInput($_GET['search'], $conn);
    
    // If location filter is set
    if (isset($_GET['location']) && !empty($_GET['location'])) {
        $location_filter = sanitizeInput($_GET['location'], $conn);
    }
    
    // If sorting option is set
    if (isset($_GET['sort']) && !empty($_GET['sort'])) {
        switch ($_GET['sort']) {
            case 'expiry_asc':
                $order_by = "expiry_date ASC";
                break;
            case 'expiry_desc':
                $order_by = "expiry_date DESC";
                break;
            case 'newest':
                $order_by = "created_at DESC";
                break;
            default:
                $order_by = "created_at DESC";
        }
    }
}

// Fetch available donations with search/filter criteria
$sql = "SELECT d.*, u.name AS donor_name FROM donations d 
        JOIN users u ON d.donor_id = u.id 
        WHERE d.status = 'available' ";

// Add search term if present
if (!empty($search)) {
    $sql .= "AND (d.food_name LIKE ? OR d.location LIKE ?) ";
}

// Add location filter if present
if (!empty($location_filter)) {
    $sql .= "AND d.location LIKE ? ";
}

// Add order by clause
$sql .= "ORDER BY " . $order_by;

// Prepare and execute the query
$stmt = mysqli_prepare($conn, $sql);

// Bind parameters if search or location filter is used
if (!empty($search) && !empty($location_filter)) {
    $search_param = "%" . $search . "%";
    $location_param = "%" . $location_filter . "%";
    mysqli_stmt_bind_param($stmt, "sss", $search_param, $search_param, $location_param);
} elseif (!empty($search)) {
    $search_param = "%" . $search . "%";
    mysqli_stmt_bind_param($stmt, "ss", $search_param, $search_param);
} elseif (!empty($location_filter)) {
    $location_param = "%" . $location_filter . "%";
    mysqli_stmt_bind_param($stmt, "s", $location_param);
}

// Execute statement
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Get all available locations for filter dropdown
$locations_sql = "SELECT DISTINCT location FROM donations WHERE status = 'available'";
$locations_result = mysqli_query($conn, $locations_sql);
$locations = [];
while ($row = mysqli_fetch_assoc($locations_result)) {
    $locations[] = $row['location'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Donations - FoodShare</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-4">
        <h2 class="mb-4">Available Food Donations</h2>
        
        <!-- Search and Filter Form -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="form-row align-items-end">
                    <div class="col-md-4 mb-2">
                        <label for="search">Search:</label>
                        <input type="text" class="form-control" id="search" name="search" 
                               placeholder="Search by food name or address" value="<?php echo $search; ?>">
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="location">Filter by Location:</label>
                        <select class="form-control" id="location" name="location">
                            <option value="">All Locations</option>
                            <?php foreach($locations as $loc): ?>
                                <option value="<?php echo $loc; ?>" <?php if($location_filter == $loc) echo "selected"; ?>>
                                    <?php echo substr($loc, 0, 30) . (strlen($loc) > 30 ? '...' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label for="sort">Sort By:</label>
                        <select class="form-control" id="sort" name="sort">
                            <option value="newest" <?php if($order_by == "created_at DESC") echo "selected"; ?>>Newest First</option>
                            <option value="expiry_asc" <?php if($order_by == "expiry_date ASC") echo "selected"; ?>>Expiry Date (Closest)</option>
                            <option value="expiry_desc" <?php if($order_by == "expiry_date DESC") echo "selected"; ?>>Expiry Date (Furthest)</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-2">
                        <button type="submit" class="btn btn-primary btn-block">Filter</button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Donations List -->
        <div class="row">
            <?php 
            if (mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) { 
            ?>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="row no-gutters">
                            <div class="col-md-4">
                                <?php if (!empty($row['image'])): ?>
                                    <img src="<?php echo $row['image']; ?>" class="card-img h-100" alt="<?php echo $row['food_name']; ?>" style="object-fit: cover;">
                                <?php else: ?>
                                    <img src="img/default-food.jpg" class="card-img h-100" alt="Default food image" style="object-fit: cover;">
                                <?php endif; ?>
                            </div>
                            <div class="col-md-8">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo $row['food_name']; ?></h5>
                                    <p class="card-text">
                                        <i class="fas fa-cubes mr-2"></i> <strong>Quantity:</strong> <?php echo $row['quantity']; ?><br>
                                        <i class="fas fa-map-marker-alt mr-2"></i> <strong>Location:</strong> <?php echo $row['location']; ?><br>
                                        <i class="fas fa-calendar-alt mr-2"></i> <strong>Expires:</strong> <?php echo date("M d, Y", strtotime($row['expiry_date'])); ?><br>
                                        <i class="fas fa-user mr-2"></i> <strong>Donor:</strong> <?php echo $row['donor_name']; ?><br>
                                        <small class="text-muted">Posted <?php echo timeAgo($row['created_at']); ?></small>
                                    </p>
                                    <form action="request.php" method="POST">
                                        <input type="hidden" name="donation_id" value="<?php echo $row['id']; ?>">
                                        <button type="submit" class="btn btn-success">Request This Donation</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php 
                }
            } else {
                echo '<div class="col-12 alert alert-info">No donations available at the moment. Please check back later.</div>';
            }
            ?>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>

<?php
// Function to calculate time ago
function timeAgo($datetime) {
    $time_ago = strtotime($datetime);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    
    $minutes = round($seconds / 60);
    $hours = round($seconds / 3600);
    $days = round($seconds / 86400);
    $weeks = round($seconds / 604800);
    $months = round($seconds / 2629440);
    $years = round($seconds / 31553280);
    
    if ($seconds <= 60) {
        return "Just now";
    } else if ($minutes <= 60) {
        return ($minutes == 1) ? "1 minute ago" : "$minutes minutes ago";
    } else if ($hours <= 24) {
        return ($hours == 1) ? "1 hour ago" : "$hours hours ago";
    } else if ($days <= 7) {
        return ($days == 1) ? "1 day ago" : "$days days ago";
    } else if ($weeks <= 4.3) {
        return ($weeks == 1) ? "1 week ago" : "$weeks weeks ago";
    } else if ($months <= 12) {
        return ($months == 1) ? "1 month ago" : "$months months ago";
    } else {
        return ($years == 1) ? "1 year ago" : "$years years ago";
    }
}
?>