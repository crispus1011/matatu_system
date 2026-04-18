-- ============================================
-- EC Matatu Public Transport System
-- Database Schema
-- ============================================

CREATE DATABASE ec_matatu_db;
USE ec_matatu_db;

-- Users Table (Passengers, Drivers, Admins)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role ENUM('passenger','driver','admin') DEFAULT 'passenger',
    profile_pic VARCHAR(255) DEFAULT NULL,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Routes Table
CREATE TABLE IF NOT EXISTS routes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_name VARCHAR(100) NOT NULL,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    distance_km DECIMAL(6,2) DEFAULT 0,
    fare DECIMAL(10,2) NOT NULL,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vehicles Table
CREATE TABLE IF NOT EXISTS vehicles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registration_no VARCHAR(20) NOT NULL UNIQUE,
    model VARCHAR(100),
    capacity INT NOT NULL DEFAULT 14,
    driver_id INT DEFAULT NULL,
    status ENUM('active','inactive','maintenance') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Trips Table
CREATE TABLE IF NOT EXISTS trips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    route_id INT NOT NULL,
    vehicle_id INT NOT NULL,
    driver_id INT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME DEFAULT NULL,
    available_seats INT NOT NULL,
    total_seats INT NOT NULL DEFAULT 14,
    status ENUM('scheduled','ongoing','completed','cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (driver_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookings Table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    trip_id INT NOT NULL,
    seat_number INT NOT NULL,
    booking_status ENUM('confirmed','cancelled','completed') DEFAULT 'confirmed',
    booking_ref VARCHAR(20) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    UNIQUE KEY unique_trip_seat (trip_id, seat_number)
);

-- Payments Table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    passenger_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash','mpesa','card') DEFAULT 'cash',
    mpesa_code VARCHAR(50) DEFAULT NULL,
    payment_status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Feedback Table
CREATE TABLE IF NOT EXISTS feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    passenger_id INT NOT NULL,
    trip_id INT NOT NULL,
    rating INT CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (passenger_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

-- Notifications Table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email)
);

-- ============================================
-- SEED DATA
-- ============================================

-- Admin user (password: admin123)
INSERT INTO users (full_name, email, phone, password, role) VALUES
('System Admin', 'admin@ecmatatu.co.ke', '0700000001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample Drivers (password: driver123)
INSERT INTO users (full_name, email, phone, password, role) VALUES
('James Otieno', 'james@ecmatatu.co.ke', '0711111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver'),
('Peter Kamau', 'peter@ecmatatu.co.ke', '0722222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver'),
('Mary Wanjiku', 'mary@ecmatatu.co.ke', '0733333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'driver');

-- Sample Passengers (password: pass123)
INSERT INTO users (full_name, email, phone, password, role) VALUES
('Alice Njeri', 'alice@gmail.com', '0744444444', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'passenger'),
('Brian Mwangi', 'brian@gmail.com', '0755555555', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'passenger'),
('Carol Akinyi', 'carol@gmail.com', '0766666666', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'passenger');

-- Routes
INSERT INTO routes (route_name, origin, destination, distance_km, fare) VALUES
('Route 1 - CBD to Githurai', 'CBD Nairobi', 'Githurai 45', 18.5, 60.00),
('Route 2 - CBD to Ruaka', 'CBD Nairobi', 'Ruaka', 22.0, 70.00),
('Route 3 - CBD to Kasarani', 'CBD Nairobi', 'Kasarani Stadium', 14.0, 50.00),
('Route 4 - CBD to Rongai', 'CBD Nairobi', 'Rongai Town', 25.0, 80.00),
('Route 5 - CBD to Eastleigh', 'CBD Nairobi', 'Eastleigh Section 3', 8.0, 40.00),
('Route 6 - CBD to Westlands', 'CBD Nairobi', 'Westlands Junction', 6.0, 30.00);

-- Vehicles
INSERT INTO vehicles (registration_no, model, capacity, driver_id) VALUES
('KCA 001A', 'Toyota Hiace 2020', 14, 2),
('KCB 002B', 'Nissan NV350 2021', 14, 3),
('KCC 003C', 'Toyota Hiace 2019', 14, 4),
('KCD 004D', 'Nissan Caravan 2022', 14, NULL);

-- Trips (upcoming)
INSERT INTO trips (route_id, vehicle_id, driver_id, departure_time, available_seats, total_seats, status) VALUES
(1, 1, 2, DATE_ADD(NOW(), INTERVAL 1 HOUR), 14, 14, 'scheduled'),
(2, 2, 3, DATE_ADD(NOW(), INTERVAL 2 HOUR), 14, 14, 'scheduled'),
(3, 3, 4, DATE_ADD(NOW(), INTERVAL 3 HOUR), 14, 14, 'scheduled'),
(4, 1, 2, DATE_ADD(NOW(), INTERVAL 4 HOUR), 14, 14, 'scheduled'),
(5, 2, 3, DATE_ADD(NOW(), INTERVAL 5 HOUR), 14, 14, 'scheduled'),
(6, 3, 4, DATE_ADD(NOW(), INTERVAL 6 HOUR), 14, 14, 'scheduled'),
(1, 1, 2, DATE_ADD(NOW(), INTERVAL 7 HOUR), 14, 14, 'scheduled'),
(2, 2, 3, DATE_ADD(NOW(), INTERVAL 8 HOUR), 14, 14, 'scheduled');
