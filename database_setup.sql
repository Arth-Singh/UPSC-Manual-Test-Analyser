-- Manual Test Analysis Platform Database Schema
-- Drop old tables and create new focused structure

-- Disable foreign key checks temporarily
SET FOREIGN_KEY_CHECKS = 0;

-- Drop existing tables if they exist (in correct order to handle foreign keys)
DROP TABLE IF EXISTS analysis_insights;
DROP TABLE IF EXISTS question_logs;
DROP TABLE IF EXISTS user_response;
DROP TABLE IF EXISTS test_attempts;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS tests;
DROP TABLE IF EXISTS test_sessions;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- Create profiles table with password protection
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    claude_api_key VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- Create test_sessions table (replaces tests)
CREATE TABLE test_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    profile_id INT NOT NULL,
    test_name VARCHAR(200) NOT NULL,
    test_date DATE NOT NULL,
    test_type VARCHAR(100) DEFAULT 'Mock Test',
    total_questions INT DEFAULT 0,
    completed_questions INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (profile_id) REFERENCES profiles(id) ON DELETE CASCADE,
    INDEX idx_profile_sessions (profile_id)
);

-- Create question_logs table (main logging table)
CREATE TABLE question_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_number INT NOT NULL,
    subject VARCHAR(100),
    topic VARCHAR(200),
    feeling ENUM('confident', 'guessed', 'confused', 'blank', 'time_pressure', 'careless') NOT NULL,
    difficulty ENUM('easy', 'medium', 'hard', 'very_hard') DEFAULT 'medium',
    time_spent INT DEFAULT NULL, -- in seconds
    correct_answer ENUM('A', 'B', 'C', 'D') DEFAULT NULL,
    my_answer ENUM('A', 'B', 'C', 'D') DEFAULT NULL,
    is_correct BOOLEAN DEFAULT NULL,
    question_text TEXT,
    explanation TEXT,
    tags VARCHAR(500),
    confidence_level INT DEFAULT 5, -- 1-10 scale
    review_needed BOOLEAN DEFAULT FALSE,
    logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE
);

-- Create analysis_insights table (for tracking patterns)
CREATE TABLE analysis_insights (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    insight_type VARCHAR(100), -- 'strength', 'weakness', 'pattern'
    insight_text TEXT,
    subject VARCHAR(100),
    metric_value DECIMAL(5,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES test_sessions(id) ON DELETE CASCADE
);

-- Create indexes for better performance
CREATE INDEX idx_question_logs_session ON question_logs(session_id);
CREATE INDEX idx_question_logs_feeling ON question_logs(feeling);
CREATE INDEX idx_question_logs_subject ON question_logs(subject);
CREATE INDEX idx_question_logs_difficulty ON question_logs(difficulty);
CREATE INDEX idx_test_sessions_date ON test_sessions(test_date);

-- Insert sample data for testing
INSERT INTO test_sessions (test_name, test_date, test_type, total_questions, notes) VALUES
('UPSC Prelims Mock Test 1', '2025-06-15', 'Mock Test', 100, 'Practice test for UPSC preparation'),
('History Practice Set', '2025-06-14', 'Subject Test', 50, 'Focused on Ancient and Medieval History');

INSERT INTO question_logs (session_id, question_number, subject, topic, feeling, difficulty, my_answer, correct_answer, is_correct, confidence_level, question_text) VALUES
(1, 1, 'History', 'Ancient India', 'confident', 'easy', 'B', 'B', TRUE, 8, 'Which dynasty ruled during the Gupta period?'),
(1, 2, 'Geography', 'Physical Geography', 'guessed', 'hard', 'C', 'A', FALSE, 3, 'What is the highest peak in Western Ghats?'),
(1, 3, 'Polity', 'Constitution', 'confused', 'medium', 'A', 'D', FALSE, 4, 'Which article deals with Right to Education?'),
(2, 1, 'History', 'Medieval India', 'confident', 'medium', 'D', 'D', TRUE, 7, 'Who founded the Delhi Sultanate?'),
(2, 2, 'History', 'Modern India', 'time_pressure', 'hard', 'B', 'C', FALSE, 5, 'When was the Quit India Movement launched?');