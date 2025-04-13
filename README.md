# FoodShare – Smart Food Donation Platform

## Overview
FoodShare is a web-based application designed to reduce food waste by connecting donors (restaurants, grocery stores, and individuals) with receivers (NGOs, shelters, and people in need). The platform provides a simple interface for donors to list surplus food items and for receivers to browse and request available donations. Administrators can manage users and donations to ensure the integrity of the system.

## Key Features
- **User Authentication**: Secure registration and login for Donors, Receivers, and Administrators.
- **Food Donation**: Donors can list food items with details such as name, quantity, location, and expiry date.
- **Donation Browsing**: Receivers can view and search available food items.
- **Request System**: Receivers can request donations, and donors can update the status of their listings.
- **Status Tracking**: Donation statuses transition through Available → Requested → Picked Up.
- **Admin Panel**: Administrators can view and manage all users and donations, and remove inappropriate content.

## Demo
Ongoing...

## Technology Stack
- **Frontend**: HTML5, CSS3, Bootstrap 4, JavaScript
- **Backend**: PHP (Core PHP)
- **Database**: MySQL
- **Server**: Apache (via XAMPP)

## Prerequisites
- PHP 7.3 or higher
- MySQL 5.0 or higher
- XAMPP (or any LAMP/WAMP stack)
- Git

## Installation
1. **Clone the repository**
   ```bash
   git clone https://github.com/pial-paul/foodshare.git
   cd foodshare
   ```
2. **Configure the database**
   - Create a MySQL database named `foodshare_db`.
   - Import the schema from `sql/foodshare_db.sql` using phpMyAdmin or the MySQL CLI:
     ```bash
     mysql -u root -p foodshare_db < sql/foodshare_db.sql
     ```
3. **Set up configuration**
   - Copy `config/config.sample.php` to `config/config.php`.
   - Update database credentials in `config/config.php`.
4. **Start the server**
   - Launch XAMPP and start Apache and MySQL.
   - Place the project folder in the `htdocs` directory.
5. **Access the application**
   - Open your browser and navigate to `http://localhost/foodshare`.

## Usage
1. **Register** as a Donor or Receiver via the registration page.
2. **Login** to access your dashboard.
3. **Donors**: Navigate to the Donate page to list food items.
4. **Receivers**: Browse available donations and request items.
5. **Donors**: Update the status of your donations in the dashboard.
6. **Administrators**: Log in to the Admin Panel to manage users and donations.

## Project Structure
```
foodshare/
├── css/                # Stylesheets
├── img/uploads/        # Uploaded food images
├── config/             # Configuration files
│   └── config.php      # Database connection settings
├── includes/           # Reusable header and footer
├── sql/                # Database schema and seed data
│   └── foodshare_db.sql
├── index.php           # Landing page
├── register.php        # User registration
├── login.php           # User login
├── logout.php          # Logout script
├── dashboard.php       # User dashboard
├── donate.php          # Donation form
├── browse.php          # Browse donations
├── request.php         # Handle requests
├── admin.php           # Admin panel
└── README.md           # Project documentation
```

## Database Schema
- **users**: `id`, `name`, `email`, `password`, `role`, `created_at`
- **donations**: `id`, `donor_id`, `food_name`, `quantity`, `location`, `expiry_date`, `image`, `status`, `created_at`
- **requests**: `id`, `receiver_id`, `donation_id`, `status`, `created_at`

## Contributing
Contributions are welcome! Please follow these steps:
1. Fork the repository.
2. Create a new branch: `git checkout -b feature/YourFeature`.
3. Commit your changes: `git commit -m 'Add some feature'`.
4. Push to the branch: `git push origin feature/YourFeature`.
5. Open a Pull Request.

## License
This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---
*Happy coding and happy sharing!*
