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
    
    -- Combat results - Attacker (primary weapon)
    crits INT DEFAULT 0 COMMENT 'Critical hits with primary weapon',
    critsm INT DEFAULT 0 COMMENT 'Critical hits with primary mining weapon',
    hits INT DEFAULT 0 COMMENT 'Total hits with primary weapon',
    hitsm INT DEFAULT 0 COMMENT 'Total hits with primary mining weapon',
    shots INT DEFAULT 0 COMMENT 'Total shots fired with primary weapon',
    shotsm INT DEFAULT 0 COMMENT 'Total shots fired with primary mining weapon',
    jams INT DEFAULT 0 COMMENT 'Weapon jams with primary weapon',

    -- Combat results - Defender (mirrors attacker fields)
    d_crits INT DEFAULT 0 COMMENT 'Defender critical hits',
    d_critsm INT DEFAULT 0 COMMENT 'Defender critical hits with mining weapon',
    d_hits INT DEFAULT 0 COMMENT 'Defender total hits',
    d_hitsm INT DEFAULT 0 COMMENT 'Defender total hits with mining weapon',
    d_shots INT DEFAULT 0 COMMENT 'Defender total shots fired',
    d_shotsm INT DEFAULT 0 COMMENT 'Defender total mining shots fired',
    d_jams INT DEFAULT 0 COMMENT 'Defender weapon jams',
    
    -- Combat results - Secondary weapon (legacy fields kept for compatibility)
    crits2 INT DEFAULT 0 COMMENT 'Critical hits with secondary weapon',
    critsm2 INT DEFAULT 0 COMMENT 'Critical hits with secondary mining weapon',
    hits2 INT DEFAULT 0 COMMENT 'Total hits with secondary weapon',
    hitsm2 INT DEFAULT 0 COMMENT 'Total hits with secondary mining weapon',
    shots2 INT DEFAULT 0 COMMENT 'Total shots fired with secondary weapon',
    shotsm2 INT DEFAULT 0 COMMENT 'Total shots fired with secondary mining weapon',
    jams2 INT DEFAULT 0 COMMENT 'Weapon jams with secondary weapon',
    
    -- Metadata
    submission_time BIGINT UNSIGNED NOT NULL COMMENT 'Unix timestamp of data submission (supports >2038)',
    
    -- Indexes for common queries
    INDEX idx_defender (defender),
    INDEX idx_tactics (tactics),
    INDEX idx_attacker (attacker),
    INDEX idx_submission_time (submission_time),
    INDEX idx_skills (tactics, hit_accuracy, maneuver, weaponry, engineering),
    INDEX idx_defender_skills (defender, tactics, hit_accuracy, maneuver, weaponry, engineering),
    INDEX idx_defender_time (defender, submission_time),
    INDEX idx_attacker_time (attacker, submission_time),
    UNIQUE KEY uk_logid (logid),

    CHECK (crits >= 0),
    CHECK (critsm >= 0),
    CHECK (hits >= 0),
    CHECK (hitsm >= 0),
    CHECK (shots >= 0),
    CHECK (shotsm >= 0),
    CHECK (jams >= 0),
    CHECK (d_crits >= 0),
    CHECK (d_critsm >= 0),
    CHECK (d_hits >= 0),
    CHECK (d_hitsm >= 0),
    CHECK (d_shots >= 0),
    CHECK (d_shotsm >= 0),
    CHECK (d_jams >= 0),
    CHECK (crits2 >= 0),
    CHECK (critsm2 >= 0),
    CHECK (hits2 >= 0),
    CHECK (hitsm2 >= 0),
    CHECK (shots2 >= 0),
    CHECK (shotsm2 >= 0),
    CHECK (jams2 >= 0)
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
