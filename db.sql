-- Lead Management System Database Schema

-- Create database
CREATE DATABASE IF NOT EXISTS u345095192_auralead;
USE u345095192_auralead;

-- Users table with is_active field
CREATE TABLE IF NOT EXISTS users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'sales', 'marketing') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table for marketing campaigns
CREATE TABLE IF NOT EXISTS services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(255) NOT NULL,
    service_category VARCHAR(100),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Running ads table for marketing campaigns
CREATE TABLE IF NOT EXISTS running_ads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ad_name VARCHAR(255) NOT NULL,
    service_id INT,
    platform VARCHAR(100),
    budget DECIMAL(10,2) DEFAULT 0,
    start_date DATE,
    end_date DATE,
    target_audience TEXT,
    ad_copy TEXT,
    assigned_sales_member INT,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE SET NULL,
    FOREIGN KEY (assigned_sales_member) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Enhanced leads table with marketing integration
CREATE TABLE IF NOT EXISTS leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(255) NOT NULL,
    required_services VARCHAR(255),
    website VARCHAR(255),
    phone VARCHAR(50),
    email VARCHAR(255),
    call_enquiry TEXT,
    mail VARCHAR(255),
    whatsapp VARCHAR(255),
    follow_up DATE,
    client_status ENUM('Interested', 'Not Interested', 'Budget Not Met', 'Meeting Scheduled', '') DEFAULT '',
    notes TEXT,
    industry VARCHAR(100),
    assigned_to INT,
    source_ad_id INT,
    lead_source VARCHAR(100) DEFAULT 'Manual',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (source_ad_id) REFERENCES running_ads(id) ON DELETE SET NULL
);

-- Insert sample users with proper password hashing
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin'),
('john_sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Sales', 'sales'),
('mary_marketing', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Marketing', 'marketing')
ON DUPLICATE KEY UPDATE username=username;

-- Insert sample services
INSERT INTO services (service_name, service_category, description, created_by) VALUES
('Website Development', 'Development Services', 'Custom website development and design', 1),
('Digital Marketing', 'Digital Marketing', 'SEO, PPC, and social media marketing', 1),
('Logo Design', 'Design Services', 'Professional logo and brand identity design', 1),
('E-commerce Development', 'Development Services', 'Online store development and setup', 1),
('Content Marketing', 'Digital Marketing', 'Content creation and marketing strategy', 1)
ON DUPLICATE KEY UPDATE service_name=service_name;

-- Insert sample running ads
INSERT INTO running_ads (ad_name, service_id, platform, budget, start_date, target_audience, ad_copy, assigned_sales_member, created_by) VALUES
('Website Dev Campaign Q4', 1, 'Facebook', 1500.00, '2024-10-01', 'Small business owners, 25-50 years', 'Transform your business with a professional website. Get started today!', 2, 3),
('Digital Marketing Push', 2, 'Google Ads', 2000.00, '2024-10-15', 'Business owners looking for online growth', 'Boost your online presence with our proven digital marketing strategies.', 2, 3),
('Logo Design Special', 3, 'Instagram', 800.00, '2024-11-01', 'Startups and new businesses', 'Professional logo design that makes your brand stand out. Limited time offer!', 2, 3)
ON DUPLICATE KEY UPDATE ad_name=ad_name;

-- Insert sample leads with enhanced data
INSERT INTO leads (client_name, required_services, website, phone, email, call_enquiry, mail, whatsapp, follow_up, client_status, notes, industry, assigned_to, source_ad_id, lead_source) VALUES
('Tech Corp Ltd', 'Web Development, SEO', 'https://techcorp.com', '+1234567890', 'contact@techcorp.com', 'Initial inquiry about web redesign', 'sent@techcorp.com', '+1234567890', '2024-12-15', 'Interested', 'Large tech company looking for complete web overhaul', 'Technology', 2, 1, 'Facebook Ad'),
('Green Energy Solutions', 'Digital Marketing, Branding', 'https://greenenergy.com', '+9876543210', 'info@greenenergy.com', 'Marketing campaign inquiry', 'marketing@greenenergy.com', '+9876543210', '2024-12-12', 'Meeting Scheduled', 'Renewable energy startup', 'Energy', 2, 2, 'Google Ad'),
('Fashion Forward', 'E-commerce Development', '', '+5555555555', 'hello@fashionforward.com', 'E-commerce platform needed', 'orders@fashionforward.com', '+5555555555', '2024-12-20', 'Budget Not Met', 'Fashion retail company', 'Retail', 2, NULL, 'Website Form'),
('Local Restaurant Chain', 'Website, Social Media', 'https://localrestaurant.com', '+1111111111', 'owner@localrestaurant.com', 'Social media management', 'info@localrestaurant.com', '+1111111111', '2024-12-18', 'Interested', 'Restaurant chain wants online presence', 'Food & Beverage', 2, NULL, 'Phone Call'),
('Healthcare Plus', 'Custom Software', '', '+2222222222', 'admin@healthcareplus.com', 'Patient management system', 'tech@healthcareplus.com', '+2222222222', '2024-12-25', 'Meeting Scheduled', 'Healthcare provider needs custom solution', 'Healthcare', 2, NULL, 'Referral')
ON DUPLICATE KEY UPDATE client_name=client_name;