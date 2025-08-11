-- Enhanced Lead Management System Database Schema
-- Adding Services and Running Ads functionality

-- Create database
CREATE DATABASE IF NOT EXISTS u345095192_auralead;
USE u345095192_auralead;

-- Users table (existing with enhancement)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    role ENUM('admin', 'sales', 'marketing') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table (NEW) - Admin managed services
CREATE TABLE services (
    id INT PRIMARY KEY AUTO_INCREMENT,
    service_name VARCHAR(255) NOT NULL,
    service_category VARCHAR(100),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
);

-- Running Ads table (NEW) - Marketing campaigns with auto lead assignment
CREATE TABLE running_ads (
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

-- Enhanced Leads table with service selection and ad source tracking
CREATE TABLE leads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(255) NOT NULL,
    required_services VARCHAR(255), -- Will store selected service IDs as comma-separated
    selected_service_ids TEXT, -- JSON array of selected service IDs
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
    source_ad_id INT, -- NEW: Track which ad generated this lead
    lead_source VARCHAR(100) DEFAULT 'Manual', -- NEW: Manual, Facebook Ad, Google Ad, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (source_ad_id) REFERENCES running_ads(id) ON DELETE SET NULL
);

-- Insert sample users (passwords are all 'password' hashed)
INSERT INTO users (username, password, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Admin', 'admin'),
('john_sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John Sales', 'sales'),
('mary_marketing', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary Marketing', 'marketing'),
('sarah_sales', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Sarah Sales', 'sales');

-- Insert sample services
INSERT INTO services (service_name, service_category, description, created_by) VALUES
('Website Development', 'Digital Services', 'Custom website design and development', 1),
('E-commerce Development', 'Digital Services', 'Online store setup and customization', 1),
('Mobile App Development', 'Digital Services', 'iOS and Android app development', 1),
('SEO Optimization', 'Digital Marketing', 'Search engine optimization services', 1),
('Social Media Marketing', 'Digital Marketing', 'Social media management and advertising', 1),
('Google Ads Management', 'Digital Marketing', 'PPC campaign setup and management', 1),
('Content Marketing', 'Digital Marketing', 'Blog writing and content strategy', 1),
('Logo Design', 'Design Services', 'Professional logo and brand identity design', 1),
('UI/UX Design', 'Design Services', 'User interface and experience design', 1),
('Graphic Design', 'Design Services', 'Print and digital graphic design services', 1),
('Business Consulting', 'Consulting Services', 'Strategic business planning and advice', 1),
('IT Consulting', 'Consulting Services', 'Technology consulting and implementation', 1);

-- Insert sample running ads
INSERT INTO running_ads (ad_name, service_id, platform, budget, start_date, assigned_sales_member, target_audience, ad_copy, created_by) VALUES
('Small Business Website Campaign', 1, 'Facebook', 500.00, '2024-08-01', 2, 'Small business owners, 25-50 years old', 'Get your professional website today! Starting at $999', 3),
('E-commerce Holiday Promo', 2, 'Google Ads', 800.00, '2024-08-01', 2, 'Retail business owners', 'Launch your online store before the holidays. Special discount!', 3),
('SEO Services LinkedIn', 4, 'LinkedIn', 300.00, '2024-08-05', 4, 'Business owners, marketing managers', 'Boost your Google rankings with our proven SEO strategies', 3),
('Social Media Management Ad', 5, 'Instagram', 400.00, '2024-08-10', 4, 'Small to medium businesses', 'Let us handle your social media while you focus on your business', 3);

-- Insert sample leads with enhanced data
INSERT INTO leads (client_name, required_services, selected_service_ids, website, phone, email, call_enquiry, mail, whatsapp, follow_up, client_status, notes, industry, assigned_to, source_ad_id, lead_source) VALUES
('Tech Innovators LLC', 'Website Development, SEO Optimization', '[1,4]', 'https://techinnovators.com', '+1234567890', 'contact@techinnovators.com', 'Interested in complete web solution', 'info@techinnovators.com', '+1234567890', '2024-08-15', 'Interested', 'Technology startup looking for web presence', 'Technology', 2, 1, 'Facebook Ad'),
('Green Earth Solutions', 'E-commerce Development, Social Media Marketing', '[2,5]', 'https://greenearthsolutions.com', '+9876543210', 'hello@greenearthsolutions.com', 'Want to sell eco-products online', 'sales@greenearthsolutions.com', '+9876543210', '2024-08-12', 'Meeting Scheduled', 'Eco-friendly products company', 'Retail', 2, 2, 'Google Ad'),
('Fashion Forward Boutique', 'E-commerce Development, UI/UX Design', '[2,9]', '', '+5555555555', 'owner@fashionforward.com', 'Need online fashion store', 'contact@fashionforward.com', '+5555555555', '2024-08-20', 'Budget Not Met', 'High-end fashion boutique', 'Retail', 2, NULL, 'Manual'),
('Local Restaurant Group', 'Website Development, Social Media Marketing', '[1,5]', 'https://localrestaurants.com', '+1111111111', 'manager@localrestaurants.com', 'Multiple locations need online presence', 'info@localrestaurants.com', '+1111111111', '2024-08-18', 'Interested', 'Restaurant chain expansion', 'Food & Beverage', 4, 4, 'Instagram Ad'),
('HealthTech Solutions', 'Mobile App Development, IT Consulting', '[3,12]', '', '+2222222222', 'ceo@healthtechsolutions.com', 'Healthcare app development needed', 'technical@healthtechsolutions.com', '+2222222222', '2024-08-25', 'Meeting Scheduled', 'Healthcare technology provider', 'Healthcare', 4, 3, 'LinkedIn Ad');

-- Create indexes for better performance
CREATE INDEX idx_leads_assigned_to ON leads(assigned_to);
CREATE INDEX idx_leads_status ON leads(client_status);
CREATE INDEX idx_leads_created_at ON leads(created_at);
CREATE INDEX idx_leads_source_ad ON leads(source_ad_id);
CREATE INDEX idx_services_active ON services(is_active);
CREATE INDEX idx_running_ads_active ON running_ads(is_active);
CREATE INDEX idx_users_role ON users(role);