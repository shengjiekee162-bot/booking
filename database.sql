-- Create database
CREATE DATABASE IF NOT EXISTS booking_jie CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE booking_jie;

-- Tables information
CREATE TABLE IF NOT EXISTS tables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number INT UNIQUE NOT NULL,
    capacity INT NOT NULL,
    description VARCHAR(255),
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample tables
INSERT INTO tables (table_number, capacity, description, available) VALUES
(1, 2, '靠窗双人桌 / Window table for 2', TRUE),
(2, 2, '双人桌 / Table for 2', TRUE),
(3, 4, '四人桌 / Table for 4', TRUE),
(4, 4, '四人桌 / Table for 4', TRUE),
(5, 6, '六人桌 / Table for 6', TRUE),
(6, 6, '靠窗六人桌 / Window table for 6', TRUE),
(7, 8, '八人大桌 / Large table for 8', TRUE),
(8, 4, '四人桌 / Table for 4', TRUE),
(9, 2, '双人桌 / Table for 2', TRUE),
(10, 10, 'VIP包厢 / VIP room for 10', TRUE);

-- Users table (for optional login)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- Customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Bookings table
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NOT NULL,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    number_of_guests INT NOT NULL,
    table_number INT,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE
);

-- Food menu table
CREATE TABLE IF NOT EXISTS food_menu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category VARCHAR(50) NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    image_url VARCHAR(255),
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Food orders table
CREATE TABLE IF NOT EXISTS food_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'confirmed', 'preparing', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    food_item_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES food_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (food_item_id) REFERENCES food_menu(id) ON DELETE CASCADE
);

-- Insert sample food menu items
INSERT INTO food_menu (name, description, category, price, available) VALUES
('Nasi Lemak', '传统马来西亚香饭配鸡肉、鸡蛋和参巴酱', 'Main Course', 12.90, TRUE),
('Char Kway Teow', '炒粿条配虾和鸡蛋', 'Main Course', 15.50, TRUE),
('Hainanese Chicken Rice', '海南鸡饭配酱料', 'Main Course', 13.90, TRUE),
('Tom Yam Soup', '泰式酸辣汤', 'Soup', 8.90, TRUE),
('Satay (10 sticks)', '沙爹串配花生酱', 'Appetizer', 12.00, TRUE),
('Spring Rolls (5 pcs)', '春卷配甜辣酱', 'Appetizer', 7.50, TRUE),
('Mango Sticky Rice', '芒果糯米饭', 'Dessert', 8.50, TRUE),
('Ice Kacang', '红豆冰', 'Dessert', 6.50, TRUE),
('Teh Tarik', '拉茶', 'Beverage', 3.50, TRUE),
('Fresh Coconut Water', '新鲜椰子水', 'Beverage', 5.00, TRUE);
