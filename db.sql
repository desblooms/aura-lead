-- Lead Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS lead_management;
USE lead_management;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'sales', 'marketing') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Leads table
CREATE TABLE leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(255) NOT NULL,
    required_services VARCHAR(255),
    website VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    call_enquiry VARCHAR(255),
    mail VARCHAR(255),
    whatsapp VARCHAR(255),
    follow_up VARCHAR(255),
    client_status ENUM('Interested', 'Not Interested', 'Budget Not Met', 'Meeting Scheduled', '') DEFAULT '',
    notes TEXT,
    industry VARCHAR(100),
    assigned_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL
);

-- Insert sample users
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin'),
('john_sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Sales', 'sales'),
('mary_marketing', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Marketing', 'marketing');

-- Insert sample leads
INSERT INTO leads (client_name, required_services, website, phone, email, call_enquiry, mail, whatsapp, follow_up, client_status, notes, industry, assigned_to) VALUES
('Tech Corp Ltd', 'Web Development, SEO', 'https://techcorp.com', '+1234567890', 'contact@techcorp.com', 'Initial inquiry about web redesign', 'Sent proposal via email', '+1234567890', '2024-08-15', 'Interested', 'Large tech company looking for complete web overhaul', 'Technology', 2),
('Green Energy Solutions', 'Digital Marketing, Branding', 'https://greenenergy.com', '+9876543210', 'info@greenenergy.com', 'Marketing campaign inquiry', 'Follow-up email scheduled', '+9876543210', '2024-08-12', 'Meeting Scheduled', 'Renewable energy startup', 'Energy', 2),
('Fashion Forward', 'E-commerce Development', '', '+5555555555', 'hello@fashionforward.com', 'E-commerce platform needed', 'Sent initial quote', '+5555555555', '2024-08-20', 'Budget Not Met', 'Fashion retail company', 'Retail', 2),
('Local Restaurant Chain', 'Website, Social Media', 'https://localrestaurant.com', '+1111111111', 'owner@localrestaurant.com', 'Social media management', 'Waiting for decision', '+1111111111', '2024-08-18', 'Interested', 'Restaurant chain wants online presence', 'Food & Beverage', 2),
('Healthcare Plus', 'Custom Software', '', '+2222222222', 'admin@healthcareplus.com', 'Patient management system', 'Demo scheduled', '+2222222222', '2024-08-25', 'Meeting Scheduled', 'Healthcare provider needs custom solution', 'Healthcare', 2);