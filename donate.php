<?php
// Include database configuration
require_once 'config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirectWithMessage("login.php", "Please login to donate food items.", "error");
}

// Check if user has the donor role
if (!hasRole("donor")) {
    redirectWithMessage("dashboard.php", "You do not have permission to access this page.", "error");
}

// Initialize variables
$food_name = $quantity = $location = $expiry_date = "";
$food_name_err = $quantity_err = $location_err = $expiry_date_err = $image_err = "";
$upload_dir = "img/uploads/";

// Process form data when submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate food name
    if (empty(trim($_POST["food_name"]))) {
        $food_name_err = "Please enter the food name.";     
    } else {
        $food_name = sanitizeInput($_POST["food_name"], $conn);
    }
    
    // Validate quantity
    if (empty(trim($_POST["quantity"]))) {
        $quantity_err = "Please enter the quantity.";     
    } else {
        $quantity = sanitizeInput($_POST["quantity"], $conn);
    }
    
    // Validate location
    if (empty(trim($_POST["location"]))) {
        $location_err = "Please enter the pickup location.";     
    } else {
        $location = sanitizeInput($_POST["location"], $conn);
    }
    
    // Validate expiry date
    if (empty(trim($_POST["expiry_date"]))) {
        $expiry_date_err = "Please enter the expiry date.";     
    } else {
        $expiry_date = sanitizeInput($_POST["expiry_date"], $conn);
        
        // Check if expiry date is in the future
        $current_date = date("Y-m-d");
        if ($expiry_date < $current_date) {
            $expiry_date_err = "Expiry date must be in the future.";
        }
    }
    
    // Process image upload if present
    $image_path = "";
    if (isset($_FILES["image"]) && $_FILES["image"]["error"] == 0) {
        $allowed_types = ["image/jpeg", "image/jpg", "image/png"];
        $file_type = $_FILES["image"]["type"];
        
        if (!in_array($file_type, $allowed_types)) {
            $image_err = "Only JPG, JPEG, and PNG files are allowed.";
        } else {
            $file_size = $_FILES["image"]["size"];
            if ($file_size > 5000000) { // 5MB max
                $image_err = "File size exceeds 5MB limit.";
            } else {
                // Create uploads directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $temp_name = $_FILES["image"]["tmp_name"];
                $filename = time() . '_' . $_FILES["image"]["name"];
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($temp_name, $target_file)) {
                    $image_path = $target_file;
                } else {
                    $image_err = "Failed to upload image. Please try again.";
                }
            }
        }
    }
    
    // Check input errors before inserting in database
    if (empty($food_name_err) && empty($quantity_err) && empty($location_err) && empty($expiry_date_err) && empty($image_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO donations (donor_id, food_name, quantity, location, expiry_date, image, status) VALUES (?, ?, ?, ?, ?, ?, 'available')";
         
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "isssss", $param_donor_id, $param_food_name, $param_quantity, $param_location, $param_expiry_date, $param_image);
            
            // Set parameters
            $param_donor_id = $_SESSION["user_id"];
            $param_food_name = $food_name;
            $param_quantity = $quantity;
            $param_location = $location;
            $param_expiry_date = $expiry_date;
            $param_image = $image_path;
            
            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Donation successful, redirect to dashboard
                redirectWithMessage("dashboard.php", "Your food donation has been posted successfully!", "success");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            mysqli_stmt_close($stmt);
        }
    }
    
    // Close connection
    mysqli_close($conn);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donate Food - FoodShare</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">Donate Food</h4>
                    </div>
                    <div class="card-body">
                        <p>Please fill in the details of the food you'd like to donate.</p>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
                            <div class="form-group">
                                <label>Food Name</label>
                                <input type="text" name="food_name" class="form-control <?php echo (!empty($food_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $food_name; ?>" placeholder="e.g., Rice, Bread, Fresh Vegetables">
                                <span class="invalid-feedback"><?php echo $food_name_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>Quantity</label>
                                <input type="text" name="quantity" class="form-control <?php echo (!empty($quantity_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $quantity; ?>" placeholder="e.g., 5 kg, 10 packets, 3 boxes">
                                <span class="invalid-feedback"><?php echo $quantity_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>Pickup Location</label>
                                <textarea name="location" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" rows="3" placeholder="Enter full address for pickup"><?php echo $location; ?></textarea>
                                <span class="invalid-feedback"><?php echo $location_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="date" name="expiry_date" class="form-control <?php echo (!empty($expiry_date_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $expiry_date; ?>">
                                <span class="invalid-feedback"><?php echo $expiry_date_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <label>Food Image (Optional)</label>
                                <input type="file" name="image" class="form-control-file <?php echo (!empty($image_err)) ? 'is-invalid' : ''; ?>">
                                <small class="form-text text-muted">Upload an image of the food (Max size: 5MB, Types: JPG, JPEG, PNG)</small>
                                <span class="invalid-feedback"><?php echo $image_err; ?></span>
                            </div>
                            
                            <div class="form-group">
                                <input type="submit" class="btn btn-success" value="Submit Donation">
                                <a href="dashboard.php" class="btn btn-secondary ml-2">Cancel</a>
                            </div>
                        </form>
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