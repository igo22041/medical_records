CREATE DATABASE IF NOT EXISTS medical_records CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE medical_records;

-- Таблица пользователей
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Таблица медицинских записей
CREATE TABLE IF NOT EXISTS medical_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    patient_name VARCHAR(100) NOT NULL,
    patient_age INT NOT NULL,
    diagnosis TEXT NOT NULL,
    treatment TEXT NOT NULL,
    doctor_name VARCHAR(100) NOT NULL,
    record_date DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_patient_name (patient_name),
    INDEX idx_record_date (record_date),
    INDEX idx_created_by (created_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Создание администратора по умолчанию (пароль: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@medical.com', '$2y$12$BGrGaH1eJvp04JG0/yyLpOJBuQJfgv.RKStzyNc.2KapeSTL6HyYW', 'admin');

-- Создание тестового пользователя (пароль: user123)
INSERT INTO users (username, email, password, role) VALUES 
('user', 'user@medical.com', '$2y$12$yUN3URe7XaO2huW.3L.Jhe8CbQi3b7CiwIJBV9j5DVT/E4eVHuFKu', 'user');

