-- Create Users Table
CREATE TABLE users (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- For storing hashed passwords
    role ENUM('donor', 'receiver', 'admin') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Donations Table
CREATE TABLE donations (
    id INT NOT NULL AUTO_INCREMENT,
    donor_id INT NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    quantity VARCHAR(50) NOT NULL,
    location TEXT NOT NULL,
    expiry_date DATE NOT NULL,
    image VARCHAR(255), -- Path to uploaded image
    status ENUM('available', 'requested', 'picked_up', 'expired') DEFAULT 'available',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (donor_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create Requests Table
CREATE TABLE requests (
    id INT NOT NULL AUTO_INCREMENT,
    receiver_id INT NOT NULL,
    donation_id INT NOT NULL,
    status ENUM('pending', 'approved', 'declined', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (donation_id) REFERENCES donations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
