-- Pardus Combat Data - Database Schema
-- This schema defines the database structure for the combat data collection system

-- Create database (if not already created via gcloud command)
-- CREATE DATABASE IF NOT EXISTS pardus_combat_data CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the database
USE pardus_combat_data;

-- Combat data table - stores all submitted combat statistics
CREATE TABLE IF NOT EXISTS combat_data (
    -- Primary key
    id INT AUTO_INCREMENT PRIMARY KEY,
    
    -- Player and combat identification
    attacker VARCHAR(255) NOT NULL COMMENT 'Attacking player name',
    ship VARCHAR(255) COMMENT 'Primary ship type used in combat',
    ship2 VARCHAR(255) COMMENT 'Secondary ship type (if applicable)',
    defender VARCHAR(255) NOT NULL COMMENT 'Defending NPC/player name',
    logid VARCHAR(255) COMMENT 'Unique combat log identifier',
    
    -- Player skills at end of combat
    tactics DECIMAL(10, 2) DEFAULT 0 COMMENT 'Tactics skill level',
    hit_accuracy DECIMAL(10, 2) DEFAULT 0 COMMENT 'Hit Accuracy skill level',
    maneuver DECIMAL(10, 2) DEFAULT 0 COMMENT 'Maneuver skill level',
    weaponry DECIMAL(10, 2) DEFAULT 0 COMMENT 'Weaponry skill level',
    engineering DECIMAL(10, 2) DEFAULT 0 COMMENT 'Engineering skill level',
    evasion DECIMAL(10, 2) DEFAULT 0 COMMENT 'Evasion skill level',
    
    -- Electronic warfare systems
    ECM VARCHAR(50) COMMENT 'Electronic Counter Measures level',
    ECCM VARCHAR(50) COMMENT 'Electronic Counter-Counter Measures level',
    
    -- Combat results - Primary weapon
    crits INT DEFAULT 0 COMMENT 'Critical hits with primary weapon',
    critsm INT DEFAULT 0 COMMENT 'Critical hits with primary mining weapon',
    hits INT DEFAULT 0 COMMENT 'Total hits with primary weapon',
    hitsm INT DEFAULT 0 COMMENT 'Total hits with primary mining weapon',
    shots INT DEFAULT 0 COMMENT 'Total shots fired with primary weapon',
    shotsm INT DEFAULT 0 COMMENT 'Total shots fired with primary mining weapon',
    jams INT DEFAULT 0 COMMENT 'Weapon jams with primary weapon',
    
    -- Combat results - Secondary weapon
    crits2 INT DEFAULT 0 COMMENT 'Critical hits with secondary weapon',
    critsm2 INT DEFAULT 0 COMMENT 'Critical hits with secondary mining weapon',
    hits2 INT DEFAULT 0 COMMENT 'Total hits with secondary weapon',
    hitsm2 INT DEFAULT 0 COMMENT 'Total hits with secondary mining weapon',
    shots2 INT DEFAULT 0 COMMENT 'Total shots fired with secondary weapon',
    shotsm2 INT DEFAULT 0 COMMENT 'Total shots fired with secondary mining weapon',
    jams2 INT DEFAULT 0 COMMENT 'Weapon jams with secondary weapon',
    
    -- Metadata
    submission_time INT NOT NULL COMMENT 'Unix timestamp of data submission',
    
    -- Indexes for common queries
    INDEX idx_defender (defender),
    INDEX idx_tactics (tactics),
    INDEX idx_attacker (attacker),
    INDEX idx_submission_time (submission_time),
    INDEX idx_skills (tactics, hit_accuracy, maneuver, weaponry, engineering)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Stores combat statistics from Pardus game';

-- Optional: Create application user and grant permissions
-- Note: Replace 'YOUR_SECURE_APP_PASSWORD' with actual password
-- This is handled by gcloud commands in the deployment guide, but included here for reference

-- CREATE USER IF NOT EXISTS 'pardus_app_user'@'%' IDENTIFIED BY 'YOUR_SECURE_APP_PASSWORD';
-- GRANT SELECT, INSERT, UPDATE ON pardus_combat_data.combat_data TO 'pardus_app_user'@'%';
-- FLUSH PRIVILEGES;

-- Verify table creation
SHOW CREATE TABLE combat_data;

-- Check table status
SHOW TABLE STATUS LIKE 'combat_data';
