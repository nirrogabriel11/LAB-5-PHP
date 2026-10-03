CREATE DATABASE IF NOT EXISTS app_db;
USE app_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS notes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Reminder',
    note_date DATE NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

INSERT INTO users (full_name, username, password_hash) VALUES
('Admin User', 'admin', '$2y$10$3G7xN0rVAhWQ1J7gK7ocmuaP9F1db0P4yHIL1lVJHvrYxK3xffhIu');

INSERT INTO notes (user_id, title, category, note_date, content) VALUES
(1, 'Birthday Reminder', 'Special Occasion', '2026-10-10', 'Buy a cake and call the family for the birthday celebration.'),
(1, 'Team Meeting', 'Event', '2026-10-07', 'Prepare agenda and send meeting notes to the team before 10 AM.'),
(1, 'Doctor Appointment', 'Reminder', '2026-10-05', 'Bring the medical card and arrive 15 minutes early.');
