-- iCapital Database Schema
-- Creates tables for investor information and document storage

-- Create database if not exists
-- CREATE DATABASE IF NOT EXISTS icapital_db;
-- USE icapital_db;
-- Using existing one from my personal DB

-- Investors table
CREATE TABLE IF NOT EXISTS investors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    date_of_birth DATE NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    street_address VARCHAR(255) NOT NULL,
    state VARCHAR(2) NOT NULL,
    zip_code VARCHAR(10) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (last_name, first_name),
    INDEX idx_created_at (created_at)
);

-- Documents table for file uploads
CREATE TABLE IF NOT EXISTS documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT NOT NULL,
    file_type VARCHAR(100) NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES investors(id) ON DELETE CASCADE,
    INDEX idx_investor_id (investor_id),
    INDEX idx_upload_date (upload_date)
);

-- Insert sample data for testing (optional)
-- INSERT INTO investors (first_name, last_name, date_of_birth, phone_number, street_address, state, zip_code) VALUES
-- ('John', 'Doe', '1985-03-15', '555-123-4567', '123 Main Street', 'NY', '10001'),
-- ('Jane', 'Smith', '1990-07-22', '555-987-6543', '456 Oak Avenue', 'CA', '90210'); 