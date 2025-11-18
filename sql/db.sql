CREATE DATABASE IF NOT EXISTS `niit_digitalID`;
USE `niit_digitalID`;

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    other_names     VARCHAR(150) NULL,
    student_id      VARCHAR(100) NOT NULL UNIQUE,
    semester_code   VARCHAR(50)  NOT NULL,
    batch_code      VARCHAR(50)  NOT NULL,
    course          VARCHAR(150) NOT NULL,
    duration        VARCHAR(100) NOT NULL,
    expiry_date     DATE         NOT NULL,
    photo           VARCHAR(255) NULL,
    signature       VARCHAR(255) NULL,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
