-- SQLite Test Database Schema for WebCalendar MCP Server Testing
-- This schema includes only the tables necessary for MCP server functionality

-- Enable foreign keys
PRAGMA foreign_keys = ON;

-- Create webcal_user table (for authentication and API tokens)
CREATE TABLE webcal_user (
  cal_login VARCHAR(25) PRIMARY KEY,
  cal_passwd VARCHAR(255),
  cal_lastname VARCHAR(25),
  cal_firstname VARCHAR(25),
  cal_is_admin CHAR(1) DEFAULT 'N',
  cal_email VARCHAR(75),
  cal_enabled CHAR(1) DEFAULT 'Y',
  cal_telephone VARCHAR(50),
  cal_address VARCHAR(75),
  cal_title VARCHAR(75),
  cal_birthday INTEGER,
  cal_last_login INTEGER,
  cal_api_token VARCHAR(255)
);

-- Create webcal_config table (for MCP server settings)
CREATE TABLE webcal_config (
  cal_setting VARCHAR(50) PRIMARY KEY,
  cal_value VARCHAR(100)
);

-- Create webcal_entry table (for event data)
CREATE TABLE webcal_entry (
  cal_id INTEGER PRIMARY KEY AUTOINCREMENT,
  cal_group_id INTEGER,
  cal_ext_for_id INTEGER,
  cal_create_by VARCHAR(25) NOT NULL,
  cal_date INTEGER NOT NULL,
  cal_time INTEGER,
  cal_mod_date INTEGER,
  cal_mod_time INTEGER,
  cal_duration INTEGER DEFAULT 0,
  cal_due_date INTEGER,
  cal_due_time INTEGER,
  cal_priority INTEGER DEFAULT 5,
  cal_type CHAR(1) DEFAULT 'E',
  cal_access CHAR(1) DEFAULT 'P',
  cal_name VARCHAR(80) NOT NULL,
  cal_location VARCHAR(100),
  cal_url VARCHAR(255),
  cal_completed INTEGER,
  cal_description TEXT
);

-- Create webcal_entry_user table (for event-user associations)
CREATE TABLE webcal_entry_user (
  cal_id INTEGER NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_status CHAR(1) DEFAULT 'A',
  cal_category INTEGER,
  cal_percent INTEGER DEFAULT 0,
  PRIMARY KEY (cal_id, cal_login)
);

-- Create webcal_categories table (for event categories)
CREATE TABLE webcal_categories (
  cat_id INTEGER PRIMARY KEY AUTOINCREMENT,
  cat_name VARCHAR(80),
  cat_owner VARCHAR(25),
  cat_order INTEGER DEFAULT 0
);

-- Create webcal_user_pref table (for user preferences)
CREATE TABLE webcal_user_pref (
  cal_login VARCHAR(25) PRIMARY KEY,
  cal_setting VARCHAR(25) NOT NULL,
  cal_value VARCHAR(100)
);

-- Create webcal_entry_log table (for activity logging)
CREATE TABLE webcal_entry_log (
  cal_log_id INTEGER PRIMARY KEY AUTOINCREMENT,
  cal_entry_id INTEGER NOT NULL,
  cal_login VARCHAR(25) NOT NULL,
  cal_user_cal VARCHAR(25),
  cal_type CHAR(1) NOT NULL,
  cal_date INTEGER NOT NULL,
  cal_time INTEGER,
  cal_text TEXT
);

-- Insert test data for MCP server testing

-- Insert test users
INSERT INTO webcal_user (cal_login, cal_firstname, cal_lastname, cal_email, cal_is_admin, cal_api_token) VALUES
('testuser', 'Test', 'User', 'test@example.com', 'N', 'test_token_12345'),
('admin', 'Admin', 'User', 'admin@example.com', 'Y', 'admin_token_67890'),
('mcp_test_user', 'MCP', 'Test', 'mcp@example.com', 'N', 'mcp_test_token');

-- Insert MCP server configuration
INSERT INTO webcal_config (cal_setting, cal_value) VALUES
('MCP_SERVER_ENABLED', 'Y'),
('MCP_RATE_LIMIT', '100'),
('LANGUAGE', 'English'),
('APPLICATION_NAME', 'WebCalendar Test');

-- Insert test events
INSERT INTO webcal_entry (cal_id, cal_create_by, cal_date, cal_name, cal_description) VALUES
(1, 'testuser', 20240601, 'Test Event 1', 'First test event for MCP testing'),
(2, 'admin', 20240602, 'Test Event 2', 'Second test event for MCP testing'),
(3, 'mcp_test_user', 20240603, 'MCP Test Event', 'Event specifically for MCP testing');

-- Insert event-user associations
INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status) VALUES
(1, 'testuser', 'A'),
(2, 'admin', 'A'),
(3, 'mcp_test_user', 'A');

-- Insert test categories
INSERT INTO webcal_categories (cat_id, cat_name, cat_owner) VALUES
(1, 'Work', ''),
(2, 'Personal', 'testuser'),
(3, 'Test', 'mcp_test_user');

-- Insert user preferences
INSERT INTO webcal_user_pref (cal_login, cal_setting, cal_value) VALUES
('testuser', 'TIMEZONE', 'America/New_York'),
('admin', 'TIMEZONE', 'America/Los_Angeles'),
('mcp_test_user', 'TIMEZONE', 'America/Chicago');

-- Create indexes for better performance
CREATE INDEX idx_webcal_entry_create_by ON webcal_entry(cal_create_by);
CREATE INDEX idx_webcal_entry_date ON webcal_entry(cal_date);
CREATE INDEX idx_webcal_entry_user_login ON webcal_entry_user(cal_login);
CREATE INDEX idx_webcal_entry_user_cal_id ON webcal_entry_user(cal_id);

-- Create view for easier event querying
CREATE VIEW webcal_entry_with_users AS
SELECT 
  e.*,
  u.cal_firstname || ' ' || u.cal_lastname as creator_name,
  u.cal_email as creator_email
FROM webcal_entry e
LEFT JOIN webcal_user u ON e.cal_create_by = u.cal_login;