-- Base de datos: credencial_virtual
CREATE DATABASE IF NOT EXISTS credencial_virtual CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE credencial_virtual;

-- Tabla de registro de usuarios
CREATE TABLE IF NOT EXISTS user_register (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    carrera VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
