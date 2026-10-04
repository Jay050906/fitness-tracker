-- Create database (For local XAMPP setup)
-- NOTE FOR CLOUD DEPLOYMENT (Render / Aiven / PlanetScale / Railway):
-- If your provider forces a specific database name, comment out the CREATE DATABASE and USE lines below.
CREATE DATABASE IF NOT EXISTS studentdb;
USE studentdb;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- Logs table
CREATE TABLE IF NOT EXISTS fitness_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    log_date DATE NOT NULL,
    height DECIMAL(5,2) NOT NULL DEFAULT 170.00,
    weight DECIMAL(5,2) NOT NULL,
    calories INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Seed user
INSERT INTO users (username, password) VALUES ('admin', 'admin123') ON DUPLICATE KEY UPDATE username=username;

-- Seed logs
INSERT INTO fitness_logs (user_id, log_date, height, weight, calories) VALUES 
(1, '2026-10-01', 175.00, 70.5, 2100),
(1, '2026-10-02', 175.00, 70.0, 1950),
(1, '2026-10-03', 175.00, 69.4, 1850);
