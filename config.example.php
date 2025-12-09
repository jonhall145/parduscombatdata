<?php
// Database configuration example
// Copy this file to config.php and update with your actual credentials

// Detect if running on Google App Engine
$onGCP = (getenv('GAE_ENV') !== false);

if ($onGCP) {
    // Google Cloud SQL connection via Unix socket
    // Replace YOUR_CONNECTION_NAME with your actual Cloud SQL connection name
    // Get it from: gcloud sql instances describe pardus-combat-db --format="value(connectionName)"
    // Format: project:region:instance (e.g., pardus-combat-data:us-central1:pardus-combat-db)
    $connectionName = getenv('CLOUD_SQL_CONNECTION_NAME') ?: 'YOUR_CONNECTION_NAME';
    $socketDir = getenv('DB_SOCKET_DIR') ?: '/cloudsql';
    
    define('DB_SOCKET', $socketDir . '/' . $connectionName);
    define('DB_USERNAME', 'pardus_app_user');
    define('DB_PASSWORD', 'YOUR_SECURE_APP_PASSWORD');
    define('DB_NAME', 'pardus_combat_data');
} else {
    // Local development settings
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'your_database_username');
    define('DB_PASSWORD', 'your_database_password');
    define('DB_NAME', 'your_database_name');
}

// Function to create database connection
function getDatabaseConnection() {
    global $onGCP;
    
    if ($onGCP) {
        // Google App Engine - connect via Unix socket
        $conn = new mysqli(null, DB_USERNAME, DB_PASSWORD, DB_NAME, null, DB_SOCKET);
    } else {
        // Standard connection for local development
        $conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    }
    
    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection failed. Please try again later.");
    }
    
    return $conn;
}
?>
